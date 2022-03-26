<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw;

use FiiSoft\Jackdaw\Comparator\Comparators;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Handler\ErrorHandler;
use FiiSoft\Jackdaw\Handler\OnError;
use FiiSoft\Jackdaw\Internal\StreamPipe;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Collaborator;
use FiiSoft\Jackdaw\Internal\Interruption;
use FiiSoft\Jackdaw\Internal\Result;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Internal\StreamApi;
use FiiSoft\Jackdaw\Internal\StreamCollection;
use FiiSoft\Jackdaw\Internal\StreamIterator;
use FiiSoft\Jackdaw\Mapper\Internal\ConditionalExtract;
use FiiSoft\Jackdaw\Mapper\Mappers;
use FiiSoft\Jackdaw\Operation\Aggregate;
use FiiSoft\Jackdaw\Operation\Assert;
use FiiSoft\Jackdaw\Operation\Chunk;
use FiiSoft\Jackdaw\Operation\CollectIn;
use FiiSoft\Jackdaw\Operation\CollectKey;
use FiiSoft\Jackdaw\Operation\Filter;
use FiiSoft\Jackdaw\Operation\FilterMany;
use FiiSoft\Jackdaw\Operation\Flat;
use FiiSoft\Jackdaw\Operation\Flip;
use FiiSoft\Jackdaw\Operation\Gather;
use FiiSoft\Jackdaw\Operation\Internal\AssertionFailed;
use FiiSoft\Jackdaw\Operation\Internal\Ending;
use FiiSoft\Jackdaw\Operation\Internal\Feed;
use FiiSoft\Jackdaw\Operation\Internal\FinalOperation;
use FiiSoft\Jackdaw\Operation\Internal\Initial;
use FiiSoft\Jackdaw\Operation\Internal\Iterate;
use FiiSoft\Jackdaw\Operation\Internal\Limitable;
use FiiSoft\Jackdaw\Operation\Limit;
use FiiSoft\Jackdaw\Operation\Map;
use FiiSoft\Jackdaw\Operation\MapFieldWhen;
use FiiSoft\Jackdaw\Operation\MapKey;
use FiiSoft\Jackdaw\Operation\MapMany;
use FiiSoft\Jackdaw\Operation\MapWhen;
use FiiSoft\Jackdaw\Operation\Operation;
use FiiSoft\Jackdaw\Operation\Reindex;
use FiiSoft\Jackdaw\Operation\Reverse;
use FiiSoft\Jackdaw\Operation\Scan;
use FiiSoft\Jackdaw\Operation\SendTo;
use FiiSoft\Jackdaw\Operation\SendToMax;
use FiiSoft\Jackdaw\Operation\SendWhen;
use FiiSoft\Jackdaw\Operation\Shuffle;
use FiiSoft\Jackdaw\Operation\Skip;
use FiiSoft\Jackdaw\Operation\Sort;
use FiiSoft\Jackdaw\Operation\SortLimited;
use FiiSoft\Jackdaw\Operation\Tail;
use FiiSoft\Jackdaw\Operation\Terminating\Collect;
use FiiSoft\Jackdaw\Operation\Terminating\Count;
use FiiSoft\Jackdaw\Operation\Terminating\Find;
use FiiSoft\Jackdaw\Operation\Terminating\First;
use FiiSoft\Jackdaw\Operation\Terminating\Fold;
use FiiSoft\Jackdaw\Operation\Terminating\GroupBy;
use FiiSoft\Jackdaw\Operation\Terminating\Has;
use FiiSoft\Jackdaw\Operation\Terminating\HasEvery;
use FiiSoft\Jackdaw\Operation\Terminating\HasOnly;
use FiiSoft\Jackdaw\Operation\Terminating\IsEmpty;
use FiiSoft\Jackdaw\Operation\Terminating\Last;
use FiiSoft\Jackdaw\Operation\Terminating\Reduce;
use FiiSoft\Jackdaw\Operation\Terminating\Until;
use FiiSoft\Jackdaw\Operation\Tokenize;
use FiiSoft\Jackdaw\Operation\Unique;
use FiiSoft\Jackdaw\Predicate\Predicates;
use FiiSoft\Jackdaw\Producer\Internal\PushProducer;
use FiiSoft\Jackdaw\Producer\MultiProducer;
use FiiSoft\Jackdaw\Producer\Producer;
use FiiSoft\Jackdaw\Producer\Producers;

final class Stream extends Collaborator implements StreamApi
{
    private Producer $producer;
    private Operation $head;
    private Operation $last;
    private Signal $signal;
    
    private ?\Generator $currentSource = null;
    
    private bool $executed = false;
    private bool $handlePush = true;
    
    /** @var Stream[] */
    private array $pushToStreams = [];
    
    /** @var \Generator[]  */
    private array $sources = [];
    
    /** @var Producer[] */
    private array $producers = [];
    
    /** @var callable[] */
    private array $onFinishHandlers = [];
    
    /** @var callable[] */
    private array $onSuccessHandlers = [];
    
    /** @var ErrorHandler[] */
    private array $onErrorHandlers = [];
    
    /** @var Operation[] */
    private array $stack = [];
    
    /**
     * @param StreamApi|Producer|Result|\Iterator|\PDOStatement|resource|array|scalar ...$elements
     * @return self
     */
    public static function of(...$elements): self
    {
        return self::from(Producers::from($elements));
    }
    
    /**
     * @param StreamApi|Producer|Result|\Iterator|\PDOStatement|resource|array $producer
     * @return self
     */
    public static function from($producer): self
    {
        return new self(Producers::getAdapter($producer));
    }
    
    public static function empty(): self
    {
        return self::from([]);
    }
    
    private function __construct(Producer $producer)
    {
        $this->producer = $producer;
        $this->signal = new Signal($this);
        
        $this->head = new Initial();
        $this->head->setNext(new Ending());
        $this->last = $this->head;
    }
    
    public function __clone()
    {
        throw new \LogicException('For many reasons, cloning of streams is strictly prohibited');
    }
    
    /**
     * @inheritdoc
     */
    public function limit(int $limit): self
    {
        return $this->chainOperation(new Limit($limit));
    }
    
    /**
     * @inheritdoc
     */
    public function skip(int $offset): self
    {
        return $this->chainOperation(new Skip($offset));
    }
    
    /**
     * @inheritdoc
     */
    public function notNull(): self
    {
        return $this->filter(Filters::notNull());
    }
    
    /**
     * @inheritdoc
     */
    public function notEmpty(): self
    {
        return $this->filter(Filters::notEmpty());
    }
    
    /**
     * @inheritdoc
     */
    public function without(array $values, int $mode = Check::VALUE): self
    {
        if (\count($values) === 1) {
            $filter = Filters::same($values[\array_key_first($values)]);
        } else {
            $filter = Filters::onlyIn($values);
        }
        
        return $this->omit($filter, $mode);
    }
    
    /**
     * @inheritdoc
     */
    public function only(array $values, int $mode = Check::VALUE): self
    {
        if (\count($values) === 1) {
            $filter = Filters::same($values[\array_key_first($values)]);
        } else {
            $filter = Filters::onlyIn($values);
        }
        
        return $this->filter($filter, $mode);
    }
    
    /**
     * @inheritdoc
     */
    public function onlyWith($keys, bool $allowNulls = false): self
    {
        return $this->filter(Filters::onlyWith($keys, $allowNulls));
    }
    
    /**
     * @inheritdoc
     */
    public function greaterThan($value): self
    {
        return $this->filter(Filters::greaterThan($value));
    }
    
    /**
     * @inheritdoc
     */
    public function greaterOrEqual($value): self
    {
        return $this->filter(Filters::greaterOrEqual($value));
    }
    
    /**
     * @inheritdoc
     */
    public function lessThan($value): self
    {
        return $this->omit(Filters::greaterOrEqual($value));
    }
    
    /**
     * @inheritdoc
     */
    public function lessOrEqual($value): self
    {
        return $this->omit(Filters::greaterThan($value));
    }
    
    /**
     * @inheritdoc
     */
    public function onlyNumeric(): self
    {
        return $this->filter(Filters::isNumeric());
    }
    
    /**
     * @inheritdoc
     */
    public function onlyIntegers(): self
    {
        return $this->filter(Filters::isInt());
    }
    
    /**
     * @inheritdoc
     */
    public function onlyStrings(): self
    {
        return $this->filter(Filters::isString());
    }
    
    /**
     * @inheritdoc
     */
    public function assert($filter, int $mode = Check::VALUE): self
    {
        return $this->chainOperation(new Assert($filter, $mode));
    }
    
    /**
     * @inheritdoc
     */
    public function trim(): self
    {
        return $this->map(Mappers::trim());
    }
    
    /**
     * @inheritdoc
     */
    public function filterBy($field, $filter): self
    {
        return $this->filter(Filters::filterBy($field, $filter));
    }
    
    /**
     * @inheritdoc
     */
    public function filter($filter, int $mode = Check::VALUE): self
    {
        return $this->chainOperation(new Filter($filter, false, $mode));
    }
    
    /**
     * @inheritdoc
     */
    public function omitBy($field, $filter): self
    {
        return $this->omit(Filters::filterBy($field, $filter));
    }
    
    /**
     * @inheritdoc
     */
    public function omit($filter, int $mode = Check::VALUE): self
    {
        return $this->chainOperation(new Filter($filter, true, $mode));
    }
    
    /**
     * @inheritdoc
     */
    public function castToInt($fields = null): self
    {
        return $this->map(Mappers::toInt($fields));
    }
    
    /**
     * @inheritdoc
     */
    public function castToFloat($fields = null): self
    {
        return $this->map(Mappers::toFloat($fields));
    }
    
    /**
     * @inheritdoc
     */
    public function castToString($fields = null): self
    {
        return $this->map(Mappers::toString($fields));
    }
    
    /**
     * @inheritdoc
     */
    public function castToBool($fields = null): self
    {
        return $this->map(Mappers::toBool($fields));
    }
    
    /**
     * @inheritdoc
     */
    public function rename($before, $after): self
    {
        return $this->remap([$before => $after]);
    }
    
    /**
     * @inheritdoc
     */
    public function remap(array $keys): self
    {
        return $this->map(Mappers::remap($keys));
    }
    
    /**
     * @inheritdoc
     */
    public function map($mapper): self
    {
        return $this->chainOperation(new Map($mapper));
    }
    
    /**
     * @inheritdoc
     */
    public function mapWhen($condition, $mapper, $elseMapper = null): self
    {
        return $this->chainOperation(new MapWhen($condition, $mapper, $elseMapper));
    }
    
    /**
     * @inheritdoc
     */
    public function mapField($field, $mapper): self
    {
        return $this->map(Mappers::mapField($field, $mapper));
    }
    
    /**
     * @inheritdoc
     */
    public function mapFieldWhen($field, $condition, $mapper, $elseMapper = null): self
    {
        return $this->chainOperation(new MapFieldWhen($field, $condition, $mapper, $elseMapper));
    }
    
    /**
     * @inheritdoc
     */
    public function mapKey($mapper): self
    {
        return $this->chainOperation(new MapKey($mapper));
    }
    
    /**
     * @inheritdoc
     */
    public function collectIn($collector, bool $preserveKeys = false): self
    {
        return $this->chainOperation(new CollectIn($collector, $preserveKeys));
    }
    
    /**
     * @inheritdoc
     */
    public function collectKeys($collector): self
    {
        return $this->chainOperation(new CollectKey($collector));
    }
    
    /**
     * @inheritdoc
     */
    public function call(...$consumers): self
    {
        return $this->chainOperation(new SendTo(...$consumers));
    }
    
    /**
     * @inheritdoc
     */
    public function callOnce($consumer): self
    {
        return $this->callMax(1, $consumer);
    }
    
    /**
     * @inheritdoc
     */
    public function callMax(int $times, $consumer): self
    {
        return $this->chainOperation(new SendToMax($times, $consumer));
    }
    
    /**
     * @inheritdoc
     */
    public function callWhen($condition, $consumer, $elseConsumer = null): self
    {
        return $this->chainOperation(new SendWhen($condition, $consumer, $elseConsumer));
    }
    
    /**
     * @inheritdoc
     */
    public function join(...$producers): self
    {
        if ($this->producer instanceof MultiProducer) {
            foreach ($producers as $producer) {
                $this->producer->addProducer(Producers::getAdapter($producer));
            }
        } else {
            $this->producer = Producers::multiSourced($this->producer, ...$producers);
        }
        
        return $this;
    }
    
    /**
     * @inheritdoc
     */
    public function unique($comparator = null, int $mode = Check::VALUE): self
    {
        return $this->chainOperation(new Unique($comparator, $mode));
    }
    
    /**
     * @inheritdoc
     */
    public function sortBy(...$fields): self
    {
        return $this->sort(Comparators::sortBy($fields));
    }
    
    /**
     * @inheritdoc
     */
    public function sort($comparator = null, int $mode = Check::VALUE): self
    {
        return $this->chainOperation(new Sort($comparator, $mode));
    }
    
    /**
     * @inheritdoc
     */
    public function best(int $limit, $comparator = null, int $mode = Check::VALUE): self
    {
        return $this->chainOperation(new SortLimited($limit, $comparator, $mode));
    }
    
    /**
     * @inheritdoc
     */
    public function rsort($comparator = null, int $mode = Check::VALUE): self
    {
        return $this->chainOperation(new Sort($comparator, $mode, true));
    }
    
    /**
     * @inheritdoc
     */
    public function worst(int $limit, $comparator = null, int $mode = Check::VALUE): self
    {
        return $this->chainOperation(new SortLimited($limit, $comparator, $mode, true));
    }
    
    /**
     * @inheritdoc
     */
    public function reverse(): self
    {
        return $this->chainOperation(new Reverse());
    }
    
    /**
     * @inheritdoc
     */
    public function shuffle(): self
    {
        return $this->chainOperation(new Shuffle());
    }
    
    /**
     * @inheritdoc
     */
    public function reindex(): self
    {
        return $this->chainOperation(new Reindex());
    }
    
    /**
     * @inheritdoc
     */
    public function flip(): self
    {
        return $this->chainOperation(new Flip());
    }
    
    /**
     * @inheritdoc
     */
    public function scan($initial, $reducer): self
    {
        return $this->chainOperation(new Scan($initial, $reducer));
    }
    
    /**
     * @inheritdoc
     */
    public function chunkAssoc(int $size): self
    {
        return $this->chunk($size, true);
    }
    
    /**
     * @inheritdoc
     */
    public function chunk(int $size, bool $preserveKeys = false): self
    {
        return $this->chainOperation(new Chunk($size, $preserveKeys));
    }
    
    /**
     * @inheritdoc
     */
    public function aggregate(array $keys): self
    {
        return $this->chainOperation(new Aggregate($keys));
    }
    
    /**
     * @inheritdoc
     */
    public function append($field, $mapper): self
    {
        return $this->map(Mappers::append($field, $mapper));
    }
    
    /**
     * @inheritdoc
     */
    public function complete($field, $mapper): self
    {
        return $this->map(Mappers::complete($field, $mapper));
    }
    
    /**
     * @inheritdoc
     */
    public function moveTo($field, $key = null): self
    {
        return $this->map(Mappers::moveTo($field, $key));
    }
    
    /**
     * @inheritdoc
     */
    public function extract($fields, $orElse = null): self
    {
        return $this->map(Mappers::extract($fields, $orElse));
    }
    
    /**
     * @inheritdoc
     */
    public function extractWhen($filter, int $mode = Check::VALUE): self
    {
        return $this->map(new ConditionalExtract($filter, $mode));
    }
    
    /**
     * @inheritdoc
     */
    public function remove(...$fields): self
    {
        if (\count($fields) === 1 && \is_array($fields[0] ?? null)) {
            $fields = $fields[0];
        }
        
        return $this->map(Mappers::remove($fields));
    }
    
    /**
     * @inheritdoc
     */
    public function removeWhen($filter, int $mode = Check::VALUE): self
    {
        return $this->map(new ConditionalExtract($filter, $mode, true));
    }
    
    /**
     * @inheritdoc
     */
    public function split(string $separator = ' '): self
    {
        return $this->map(Mappers::split($separator));
    }
    
    /**
     * @inheritdoc
     */
    public function concat(string $separtor = ' '): self
    {
        return $this->map(Mappers::concat($separtor));
    }
    
    /**
     * @inheritdoc
     */
    public function tokenize(string $tokens = ' '): self
    {
        return $this->chainOperation(new Tokenize($tokens));
    }
    
    /**
     * @inheritdoc
     */
    public function flat(int $level = 0): self
    {
        return $this->chainOperation(new Flat($level));
    }
    
    /**
     * @inheritdoc
     */
    public function flatMap($mapper, int $level = 0): self
    {
        return $this->map($mapper)->flat($level);
    }
    
    /**
     * @inheritdoc
     */
    public function feed(StreamPipe $stream): self
    {
        $id = \spl_object_id($stream);
    
        if (!isset($this->pushToStreams[$id])) {
            $stream->prepareSubstream();
            $this->pushToStreams[$id] = $stream;
        }
        
        return $this->chainOperation(new Feed($stream));
    }
    
    /**
     * @inheritdoc
     */
    public function while($condition, int $mode = Check::VALUE): self
    {
        return $this->chainOperation(new Until($condition, $mode, true));
    }
    
    /**
     * @inheritdoc
     */
    public function until($condition, int $mode = Check::VALUE): self
    {
        return $this->chainOperation(new Until($condition, $mode));
    }
    
    /**
     * @inheritdoc
     */
    public function tail(int $numOfItems): self
    {
        return $this->chainOperation(new Tail($numOfItems));
    }
    
    /**
     * @inheritdoc
     */
    public function gather(bool $preserveKeys = false): self
    {
        return $this->chainOperation(new Gather($preserveKeys));
    }
    
    /**
     * @inheritdoc
     */
    public function onError($handler, bool $replace = false): self
    {
        if (\is_callable($handler)) {
            $handler = OnError::call($handler);
        }
    
        if ($handler instanceof ErrorHandler) {
            if ($replace) {
                $this->onErrorHandlers = [$handler];
            } else {
                $this->onErrorHandlers[] = $handler;
            }
        } else {
            throw new \InvalidArgumentException('Invalid param handler');
        }
    
        return $this;
    }
    
    /**
     * @inheritdoc
     */
    public function onSuccess(callable $handler, bool $replace = false): self
    {
        if ($replace) {
            $this->onSuccessHandlers = [$handler];
        } else {
            $this->onSuccessHandlers[] = $handler;
        }
        
        return $this;
    }
    
    /**
     * @inheritdoc
     */
    public function onFinish(callable $handler, bool $replace = false): self
    {
        if ($replace) {
            $this->onFinishHandlers = [$handler];
        } else {
            $this->onFinishHandlers[] = $handler;
        }
        
        return $this;
    }
    
    /**
     * @inheritdoc
     */
    public function toJsonAssoc(int $flags = 0): string
    {
        return $this->toJson($flags, true);
    }
    
    /**
     * @inheritdoc
     */
    public function toJson(int $flags = 0, bool $preserveKeys = false): string
    {
        return \json_encode($this->toArray($preserveKeys), \JSON_THROW_ON_ERROR | $flags);
    }
    
    /**
     * @inheritdoc
     */
    public function toString(string $separator = ','): string
    {
        return \implode($separator, $this->toArray());
    }
    
    /**
     * @inheritdoc
     */
    public function toArrayAssoc(): array
    {
        return $this->toArray(true);
    }
    
    /**
     * @inheritdoc
     */
    public function toArray(bool $preserveKeys = false): array
    {
        $buffer = new \ArrayIterator();
        $this->runWith(new CollectIn($buffer, $preserveKeys));
        
        return $buffer->getArrayCopy();
    }
    
    /**
     * @inheritdoc
     */
    public function collect(): Result
    {
        return $this->runLast(new Collect($this));
    }
    
    /**
     * @inheritdoc
     */
    public function count(): Result
    {
        return $this->runLast(new Count($this));
    }
    
    /**
     * @inheritdoc
     */
    public function reduce($reducer, $orElse = null): Result
    {
        return $this->runLast(new Reduce($this, $reducer, $orElse));
    }
    
    /**
     * @inheritdoc
     */
    public function fold($initial, $reducer): Result
    {
        return $this->runLast(new Fold($this, $initial, $reducer));
    }
    
    /**
     * @inheritdoc
     */
    public function isNotEmpty(): Result
    {
        return $this->runLast(new IsEmpty($this, false));
    }
    
    /**
     * @inheritdoc
     */
    public function isEmpty(): Result
    {
        return $this->runLast(new IsEmpty($this, true));
    }
    
    /**
     * @inheritdoc
     */
    public function has($value, int $mode = Check::VALUE): Result
    {
        return $this->runLast(new Has($this, $value, $mode));
    }
    
    /**
     * @inheritdoc
     */
    public function hasAny(array $values, int $mode = Check::VALUE): Result
    {
        return $this->has(Predicates::inArray($values), $mode);
    }
    
    /**
     * @inheritdoc
     */
    public function hasEvery(array $values, int $mode = Check::VALUE): Result
    {
        return $this->runLast(new HasEvery($this, $values, $mode));
    }
    
    /**
     * @inheritdoc
     */
    public function hasOnly(array $values, int $mode = Check::VALUE): Result
    {
        return $this->runLast(new HasOnly($this, $values, $mode));
    }
    
    /**
     * @inheritdoc
     */
    public function find($predicate, int $mode = Check::VALUE): Result
    {
        return $this->runLast(new Find($this, $predicate, $mode));
    }
    
    /**
     * @inheritdoc
     */
    public function first($orElse = null): Result
    {
        return $this->runLast(new First($this, $orElse));
    }
    
    /**
     * @inheritdoc
     */
    public function last($orElse = null): Result
    {
        return $this->runLast(new Last($this, $orElse));
    }
    
    /**
     * @inheritdoc
     */
    public function groupBy($discriminator, bool $preserveKeys = false): StreamCollection
    {
        $groupBy = new GroupBy($discriminator, $preserveKeys);
        $this->runWith($groupBy);
        
        return $groupBy->result();
    }
    
    /**
     * @inheritdoc
     */
    public function forEach(...$consumer): void
    {
        $this->runWith(new SendTo(...$consumer));
    }
    
    private function runWith(Operation $operation): void
    {
        $this->chainOperation($operation)->run();
    }
    
    private function runLast(FinalOperation $operation): Result
    {
        $this->chainOperation($operation);
        
        return $operation->result();
    }
    
    /**
     * @inheritdoc
     */
    public function run(): void
    {
        $this->prepareToRun();
        $this->continueIteration();
        $this->finish();
    }
    
    /**
     * @inheritdoc
     */
    public function loop(bool $run = false): StreamPipe
    {
        $this->feed($this);
    
        if ($run) {
            $this->run();
        }
        
        return $this;
    }
    
    protected function finish(): void
    {
        $this->executed = true;
        $this->finishSubstreems();
    
        $signal = $this->signal;
        if (!$signal->isError) {
            foreach ($this->onSuccessHandlers as $handler) {
                $handler();
            }
        }
    
        foreach ($this->onFinishHandlers as $handler) {
            $handler();
        }
    }
    
    private function prepareToRun(): void
    {
        if ($this->executed) {
            throw new \LogicException('Stream can be executed only once!');
        }
        
        $this->head = $this->head->removeFromChain();
    }
    
    protected function continueIteration(bool $once = false): bool
    {
        try {
            ITERATION_LOOP:
            if ($this->signal->isWorking) {
                
                PROCESS_NEXT_ITEM:
                if ($this->hasNextItem()) {
                    $this->head->handle($this->signal);
                } elseif (empty($this->stack)) {
                    $this->signal->streamIsEmpty();
                } else {
                    if (!empty($this->sources)) {
                        $this->currentSource = \array_pop($this->sources);
                        $this->producer = \array_pop($this->producers);
                    }
                    
                    $this->head = \array_pop($this->stack);
                    $this->signal->resume();
                }
    
                if ($once) {
                    return true;
                }
                
                goto ITERATION_LOOP;
            }

            if ($this->head->streamingFinished($this->signal)) {
                goto PROCESS_NEXT_ITEM;
            }
            
        } catch (Interruption|AssertionFailed $e) {
            throw $e;
        } catch (\Throwable $e) {
            foreach ($this->onErrorHandlers as $handler) {
                $skip = $handler->handle($e, $this->signal->item->key, $this->signal->item->value);
                if ($skip === true) {
                    goto ITERATION_LOOP;
                }

                if ($skip === false) {
                    $this->signal->abort();
                    return false;
                }
            }
            
            throw $e;
        }
        
        return false;
    }
    
    private function hasNextItem(): bool
    {
        if ($this->currentSource === null) {
            $this->currentSource = $this->producer->feed($this->signal->item);
        } else {
            $this->currentSource->next();
        }
    
        return $this->currentSource->valid();
    }
    
    protected function restartWith(Producer $producer, Operation $operation): void
    {
        $this->currentSource = null;
        $this->producer = $producer;
        $this->head = $operation;
    }
    
    protected function continueWith(Producer $producer, Operation $operation): void
    {
        $this->producers[] = $this->producer;
        $this->producer = $producer;
        
        $this->sources[] = $this->currentSource;
        $this->currentSource = null;
        
        $this->stack[] = $this->head;
        $this->head = $operation;
    }
    
    /**
     * @inheritdoc
     */
    public function getIterator(): \Traversable
    {
        $this->chainOperation(new Iterate());
        
        return new StreamIterator($this, $this->signal->item);
    }
    
    private function chainOperation(Operation $next): self
    {
        if ($this->last->isLazy()) {
            throw new \LogicException('You cannot chain next operation to lazy one');
        }
    
        if ($this->canAddNext($next)) {
            $this->last = $this->last->setNext($next);
        }
    
        return $this;
    }
    
    private function canAddNext(Operation $next): bool
    {
        if ($next instanceof Filter) {
            if ($this->last instanceof FilterMany) {
                $this->last->add($next);
                return false;
            }
            if ($this->last instanceof Filter) {
                $filterMany = new FilterMany($this->last, $next);
                $this->last = $this->last->removeFromChain();
                $this->chainOperation($filterMany);
                return false;
            }
        } elseif ($next instanceof Map) {
            if ($this->last instanceof MapMany) {
                $this->last->add($next);
                return false;
            }
            if ($this->last instanceof Map) {
                if (!$this->last->mergeWith($next)) {
                    $mapMany = new MapMany($this->last, $next);
                    $this->last = $this->last->removeFromChain();
                    $this->chainOperation($mapMany);
                }
                return false;
            }
        } elseif ($next instanceof Limit) {
            if ($this->last instanceof Limitable) {
                $this->last->applyLimit($next->limit());
                return false;
            }
            if ($this->last instanceof Sort) {
                $sortLimited = $this->last->createSortLimited($next->limit());
                $this->last = $this->last->removeFromChain();
                $this->chainOperation($sortLimited);
                return false;
            }
            if ($this->last instanceof Reverse) {
                $this->last = $this->last->removeFromChain();
                $this->tail($next->limit())->reverse();
                return false;
            }
        } elseif ($next instanceof Skip) {
            if ($this->last instanceof Skip) {
                $this->last->mergeWith($next);
                return false;
            }
        } elseif ($next instanceof Reverse) {
            if ($this->last instanceof Reverse) {
                $this->last = $this->last->removeFromChain();
                return false;
            }
            if ($this->last instanceof Sort) {
                $this->last->reverseOrder();
                return false;
            }
        } elseif ($next instanceof Reindex) {
            if ($this->last instanceof Reindex) {
                return false;
            }
        } elseif ($next instanceof Flip) {
            if ($this->last instanceof Flip) {
                $this->last = $this->last->removeFromChain();
                return false;
            }
        } elseif ($next instanceof Shuffle) {
            if ($this->last instanceof Shuffle) {
                return false;
            }
            if ($this->last instanceof Sort) {
                $this->last = $this->last->removeFromChain();
            }
        } elseif ($next instanceof Tail) {
            if ($this->last instanceof Tail) {
                $this->last->mergeWith($next);
                return false;
            }
            if ($this->last instanceof Sort) {
                $this->last->reverseOrder();
                $this->limit($next->length())->reverse();
                return false;
            }
            if ($this->last instanceof Limitable) {
                if ($this->last->limit() > $next->length()) {
                    $this->skip($this->last->limit() - $next->length());
                }
                return false;
            }
        } elseif ($next instanceof Flat) {
            if ($this->last instanceof Flat) {
                $this->last->mergeWith($next);
                return false;
            }
            if ($this->last instanceof Map) {
                $mapper = $this->last->mapper();
                if ($mapper instanceof Mapper\Tokenize) {
                    $this->last = $this->last->removeFromChain();
                    $this->tokenize($mapper->tokens());
                    return false;
                }
            }
            if ($this->last instanceof Gather) {
                $preserveKeys = $this->last->preserveKeys();
                $this->last = $this->last->removeFromChain();
                if (!$preserveKeys) {
                    $this->reindex();
                }
                if ($next->isLevel(1)) {
                    return false;
                }
                if (!$next->isLevel(0)) {
                    $next->decreaseLevel();
                }
            }
        } elseif ($next instanceof SendTo) {
            if ($this->last instanceof SendTo) {
                $this->last->mergeWith($next);
                return false;
            }
        } elseif ($next instanceof Sort) {
            if ($this->last instanceof Shuffle) {
                $this->last = $this->last->removeFromChain();
            }
        } elseif ($next instanceof Gather) {
            if ($this->last instanceof Reindex) {
                $this->last = $this->last->removeFromChain();
                $next->reindexKeys();
            }
        }
        
        return true;
    }
    
    protected function limitReached(Operation $operation): void
    {
        $this->head = $operation;
        $this->stack = [];
    }
    
    protected function sendTo(StreamPipe $stream): bool
    {
        return $stream->processExternalPush($this);
    }
    
    protected function processExternalPush(Stream $sender): bool
    {
        if ($this->handlePush) {
            $this->handlePush = false;
            $this->currentSource->send($sender->signal->item);
    
            try {
                return $this->continueIteration(true);
            } finally {
                $this->handlePush = true;
            }
        }
    
        $this->currentSource->send($sender->signal->item);
        
        return true;
    }
    
    protected function prepareSubstream(): void
    {
        if (! $this->producer instanceof PushProducer) {
            $this->producer = new PushProducer($this->producer);
        }
        
        $this->currentSource = $this->producer->feed($this->signal->item);
    }
    
    private function finishSubstreems(): void
    {
        while (!empty($this->pushToStreams)) {
            foreach ($this->pushToStreams as $key => $stream) {
                if (!$stream->continueIteration(true)) {
                    unset($this->pushToStreams[$key]);
                }
            }
        }
    }
}