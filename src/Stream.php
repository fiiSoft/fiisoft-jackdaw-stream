<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw;

use FiiSoft\Jackdaw\Comparator\Comparators;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Internal\BaseStreamPipe;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Collaborator;
use FiiSoft\Jackdaw\Internal\Interruption;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Result;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Internal\StreamApi;
use FiiSoft\Jackdaw\Internal\StreamCollection;
use FiiSoft\Jackdaw\Internal\StreamIterator;
use FiiSoft\Jackdaw\Internal\StreamPipe;
use FiiSoft\Jackdaw\Mapper\Mappers;
use FiiSoft\Jackdaw\Operation\Aggregate;
use FiiSoft\Jackdaw\Operation\Chunk;
use FiiSoft\Jackdaw\Operation\CollectIn;
use FiiSoft\Jackdaw\Operation\CollectKey;
use FiiSoft\Jackdaw\Operation\Filter;
use FiiSoft\Jackdaw\Operation\Flat;
use FiiSoft\Jackdaw\Operation\Flip;
use FiiSoft\Jackdaw\Operation\Internal\Ending;
use FiiSoft\Jackdaw\Operation\Internal\Feed;
use FiiSoft\Jackdaw\Operation\Internal\FinalOperation;
use FiiSoft\Jackdaw\Operation\Internal\Initial;
use FiiSoft\Jackdaw\Operation\Internal\Iterate;
use FiiSoft\Jackdaw\Operation\Limit;
use FiiSoft\Jackdaw\Operation\Map;
use FiiSoft\Jackdaw\Operation\MapKey;
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
use FiiSoft\Jackdaw\Operation\Unique;
use FiiSoft\Jackdaw\Predicate\Predicates;
use FiiSoft\Jackdaw\Producer\MultiProducer;
use FiiSoft\Jackdaw\Producer\Producer;
use FiiSoft\Jackdaw\Producer\Producers;

final class Stream extends Collaborator implements StreamApi
{
    /** @var Producer */
    private $producer;
    
    /** @var Operation */
    private $head;
    
    /** @var Operation */
    private $last;
    
    /** @var \Generator|null */
    private $producerIterator = null;
    
    /** @var Signal|null */
    private $signal = null;
    
    /** @var bool */
    private $executed = false;
    
    /** @var Item[] */
    private $extraItems = [];
    
    /** @var Operation[] */
    private $stack = [];
    
    /** @var \SplObjectStorage|Stream[]|null */
    private $pushToStreams = null;
    
    public static function of(...$elements): StreamApi
    {
        return self::from(Producers::from($elements));
    }
    
    public static function empty(): StreamApi
    {
        return self::from([]);
    }
    
    public static function from($producer): StreamApi
    {
        return new self(Producers::getAdapter($producer));
    }
    
    private function __construct(Producer $producer)
    {
        $this->producer = $producer;
        $this->signal = new Signal($this);
        
        $this->head = new Initial();
        $this->last = $this->head;
        $this->last->setNext(new Ending());
    }
    
    public function __clone()
    {
        throw new \LogicException('For many reasons, cloning of streams is strictly prohibited');
    }
    
    /**
     * @inheritdoc
     */
    public function limit(int $limit): StreamApi
    {
        return $this->chainOperation(new Limit($limit));
    }
    
    /**
     * @inheritdoc
     */
    public function skip(int $offset): StreamApi
    {
        return $this->chainOperation(new Skip($offset));
    }
    
    /**
     * @inheritdoc
     */
    public function notNull(): StreamApi
    {
        return $this->filter(Filters::notNull());
    }
    
    /**
     * @inheritdoc
     */
    public function notEmpty(): StreamApi
    {
        return $this->filter(Filters::notEmpty());
    }
    
    /**
     * @inheritdoc
     */
    public function without(array $values, int $mode = Check::VALUE): StreamApi
    {
        if (\count($values) === 1) {
            $filter = Filters::equal(\reset($values));
        } else {
            $filter = Filters::onlyIn($values);
        }
        
        return $this->omit($filter, $mode);
    }
    
    /**
     * @inheritdoc
     */
    public function only(array $values, int $mode = Check::VALUE): StreamApi
    {
        if (\count($values) === 1) {
            $filter = Filters::equal(\reset($values));
        } else {
            $filter = Filters::onlyIn($values);
        }
        
        return $this->filter($filter, $mode);
    }
    
    /**
     * @inheritdoc
     */
    public function onlyWith($keys, bool $allowNulls = false): StreamApi
    {
        return $this->filter(Filters::onlyWith($keys, $allowNulls));
    }
    
    /**
     * @inheritdoc
     */
    public function greaterThan($value): StreamApi
    {
        return $this->filter(Filters::greaterThan($value));
    }
    
    /**
     * @inheritdoc
     */
    public function greaterOrEqual($value): StreamApi
    {
        return $this->filter(Filters::greaterOrEqual($value));
    }
    
    /**
     * @inheritdoc
     */
    public function lessThan($value): StreamApi
    {
        return $this->omit(Filters::greaterOrEqual($value));
    }
    
    /**
     * @inheritdoc
     */
    public function lessOrEqual($value): StreamApi
    {
        return $this->omit(Filters::greaterThan($value));
    }
    
    /**
     * @inheritdoc
     */
    public function onlyNumeric(): StreamApi
    {
        return $this->filter(Filters::isNumeric());
    }
    
    /**
     * @inheritdoc
     */
    public function onlyIntegers(): StreamApi
    {
        return $this->filter(Filters::isInt());
    }
    
    /**
     * @inheritdoc
     */
    public function onlyStrings(): StreamApi
    {
        return $this->filter(Filters::isString());
    }
    
    /**
     * @inheritdoc
     */
    public function filterBy($field, $filter): StreamApi
    {
        return $this->filter(Filters::filterBy($field, $filter));
    }
    
    /**
     * @inheritdoc
     */
    public function filter($filter, int $mode = Check::VALUE): StreamApi
    {
        return $this->chainOperation(new Filter($filter, false, $mode));
    }
    
    /**
     * @inheritdoc
     */
    public function omit($filter, int $mode = Check::VALUE): StreamApi
    {
        return $this->chainOperation(new Filter($filter, true, $mode));
    }
    
    /**
     * @inheritdoc
     */
    public function castToInt($fields = null): StreamApi
    {
        return $this->map(Mappers::toInt($fields));
    }
    
    /**
     * @inheritdoc
     */
    public function map($mapper): StreamApi
    {
        return $this->chainOperation(new Map($mapper));
    }
    
    /**
     * @inheritdoc
     */
    public function mapWhen($condition, $mapper, $elseMapper = null): StreamApi
    {
        return $this->chainOperation(new MapWhen($condition, $mapper, $elseMapper));
    }
    
    /**
     * @inheritdoc
     */
    public function mapKey($mapper): StreamApi
    {
        return $this->chainOperation(new MapKey($mapper));
    }
    
    /**
     * @inheritdoc
     */
    public function collectIn($collector, bool $preserveKeys = false): StreamApi
    {
        return $this->chainOperation(new CollectIn($collector, $preserveKeys));
    }
    
    /**
     * @inheritdoc
     */
    public function collectKeys($collector): StreamApi
    {
        return $this->chainOperation(new CollectKey($collector));
    }
    
    /**
     * @inheritdoc
     */
    public function call($consumer): StreamApi
    {
        return $this->chainOperation(new SendTo($consumer));
    }
    
    /**
     * @inheritdoc
     */
    public function callOnce($consumer): StreamApi
    {
        return $this->callMax(1, $consumer);
    }
    
    /**
     * @inheritdoc
     */
    public function callMax(int $times, $consumer): StreamApi
    {
        return $this->chainOperation(new SendToMax($times, $consumer));
    }
    
    /**
     * @inheritdoc
     */
    public function callWhen($condition, $consumer, $elseConsumer = null): StreamApi
    {
        return $this->chainOperation(new SendWhen($condition, $consumer, $elseConsumer));
    }
    
    /**
     * @inheritdoc
     */
    public function join($producer): StreamApi
    {
        if ($this->producer instanceof MultiProducer) {
            $this->producer->addProducer(Producers::getAdapter($producer));
        } else {
            $this->producer = Producers::multiSourced($this->producer, $producer);
        }
        
        return $this;
    }
    
    /**
     * @inheritdoc
     */
    public function unique($comparator = null, int $mode = Check::VALUE): StreamApi
    {
        return $this->chainOperation(new Unique($comparator, $mode));
    }
    
    /**
     * @inheritdoc
     */
    public function sortBy(...$fields): StreamApi
    {
        return $this->sort(Comparators::sortBy($fields));
    }
    
    /**
     * @inheritdoc
     */
    public function sort($comparator = null, int $mode = Check::VALUE): StreamApi
    {
        return $this->chainOperation(new Sort($comparator, $mode));
    }
    
    /**
     * @inheritdoc
     */
    public function rsort($comparator = null, int $mode = Check::VALUE): StreamApi
    {
        return $this->chainOperation(new Sort($comparator, $mode, true));
    }
    
    /**
     * @inheritdoc
     */
    public function reverse(): StreamApi
    {
        return $this->chainOperation(new Reverse());
    }
    
    /**
     * @inheritdoc
     */
    public function shuffle(): StreamApi
    {
        return $this->chainOperation(new Shuffle());
    }
    
    /**
     * @inheritdoc
     */
    public function reindex(): StreamApi
    {
        return $this->chainOperation(new Reindex());
    }
    
    /**
     * @inheritdoc
     */
    public function flip(): StreamApi
    {
        return $this->chainOperation(new Flip());
    }
    
    /**
     * @inheritdoc
     */
    public function scan($initial, $reducer): StreamApi
    {
        return $this->chainOperation(new Scan($initial, $reducer));
    }
    
    /**
     * @inheritdoc
     */
    public function chunkAssoc(int $size): StreamApi
    {
        return $this->chunk($size, true);
    }
    
    /**
     * @inheritdoc
     */
    public function chunk(int $size, bool $preserveKeys = false): StreamApi
    {
        return $this->chainOperation(new Chunk($size, $preserveKeys));
    }
    
    public function aggregate(array $keys): StreamApi
    {
        return $this->chainOperation(new Aggregate($keys));
    }
    
    /**
     * @inheritdoc
     */
    public function append($field, $mapper): StreamApi
    {
        return $this->map(Mappers::append($field, $mapper));
    }
    
    /**
     * @inheritdoc
     */
    public function complete($field, $mapper): StreamApi
    {
        return $this->map(Mappers::complete($field, $mapper));
    }
    
    /**
     * @inheritdoc
     */
    public function moveTo($field): StreamApi
    {
        return $this->map(Mappers::moveTo($field));
    }
    
    /**
     * @inheritdoc
     */
    public function extract($fields, $orElse = null): StreamApi
    {
        return $this->map(Mappers::extract($fields, $orElse));
    }
    
    /**
     * @inheritdoc
     */
    public function remove(...$fields): StreamApi
    {
        if (\count($fields) === 1 && \is_array($fields[0] ?? null)) {
            $fields = $fields[0];
        }
        
        return $this->map(Mappers::remove($fields));
    }
    
    /**
     * @inheritdoc
     */
    public function split(string $separator = ' '): StreamApi
    {
        return $this->map(Mappers::split($separator));
    }
    
    /**
     * @inheritdoc
     */
    public function flat(int $level = 0): StreamApi
    {
        return $this->chainOperation(new Flat($level));
    }
    
    /**
     * @inheritdoc
     */
    public function flatMap($mapper, int $level = 0): StreamApi
    {
        return $this->map($mapper)->flat($level);
    }
    
    /**
     * @inheritdoc
     */
    public function feed(StreamPipe $stream): StreamApi
    {
        if ($this->pushToStreams === null) {
            $this->pushToStreams = new \SplObjectStorage();
        }
        
        $this->pushToStreams->attach($stream);
        
        return $this->chainOperation(new Feed($stream));
    }
    
    /**
     * @inheritdoc
     */
    public function while($condition, int $mode = Check::VALUE): StreamApi
    {
        return $this->chainOperation(new Until($condition, $mode, true));
    }
    
    /**
     * @inheritdoc
     */
    public function until($condition, int $mode = Check::VALUE): StreamApi
    {
        return $this->chainOperation(new Until($condition, $mode));
    }
    
    /**
     * @inheritdoc
     */
    public function tail(int $numOfItems): StreamApi
    {
        return $this->chainOperation(new Tail($numOfItems));
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
        return \json_encode($this->toArray($preserveKeys), $flags);
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
    public function groupBy($discriminator): StreamCollection
    {
        $groupBy = new GroupBy($discriminator);
        $this->runWith($groupBy);
        
        return $groupBy->result();
    }
    
    /**
     * @inheritdoc
     */
    public function forEach($consumer)
    {
        $this->runWith(new SendTo($consumer));
    }
    
    private function runWith(Operation $operation)
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
    public function run()
    {
        $this->prepareToRun();
        $this->continueIteration();
        $this->executed = true;
        $this->finishSubstreems();
    }
    
    private function prepareToRun()
    {
        if ($this->executed) {
            throw new \LogicException('Stream can be executed only once!');
        }
    
        if ($this->signal === null) {
            $this->signal = new Signal($this);
        }
    }
    
    protected function continueIteration(bool $once = false): bool
    {
        do {
            while (!$this->signal->isStopped()) {
                if ($this->hasNextItem()) {
                    $this->head->handle($this->signal);
                    
                    if ($this->signal->isInterrupted()) {
                        throw new Interruption();
                    }
                } elseif (empty($this->stack)) {
                    $this->signal->streamIsEmpty();
                } else {
                    $this->head = \array_pop($this->stack);
                    $this->signal->resume();
                }
    
                if ($once) {
                    return true;
                }
            }
            
            $this->head->streamingFinished($this->signal);
        } while (!$this->signal->isStopped());
        
        return false;
    }
    
    private function hasNextItem(): bool
    {
        if (!empty($this->extraItems)) {
            $nextItem = \array_shift($this->extraItems);
            $this->signal->item->key = $nextItem->key;
            $this->signal->item->value = $nextItem->value;
            return true;
        }
        
        if ($this->signal->isFinished()) {
            return false;
        }
    
        if ($this->producerIterator === null) {
            $this->producerIterator = $this->producer->feed($this->signal->item);
        } else {
            $this->producerIterator->next();
        }
    
        return $this->producerIterator->valid();
    }
    
    protected function restartFrom(Operation $operation, array $items)
    {
        $this->head = $operation;
        $this->extraItems = empty($this->extraItems) ? $items : \array_merge($this->extraItems, $items);
    }
    
    protected function continueFrom(Operation $operation, array $items)
    {
        $this->stack[] = $this->head;
        $this->head = $operation;
        $this->extraItems = empty($this->extraItems) ? $items : \array_merge($this->extraItems, $items);
    }
    
    /**
     * @inheritdoc
     */
    public function getIterator(): \Traversable
    {
        $iterator = new StreamIterator($this);
        $this->chainOperation(new Iterate($iterator));
        
        return $iterator;
    }
    
    private function chainOperation(Operation $next): StreamApi
    {
        if ($this->last->isLazy()) {
            throw new \LogicException('You cannot chain next operation to lazy one');
        }
        
        $this->last = $this->last->setNext($next);
        
        if ($this->head instanceof Initial) {
            $this->head = $next;
        }
        
        return $this;
    }
    
    protected function limitReached(Operation $operation)
    {
        $this->head = $operation;
        $this->stack = [];
    }
    
    protected function streamIsEmpty()
    {
        $this->extraItems = [];
    }
    
    protected function sendTo(BaseStreamPipe $stream): bool
    {
        return $this->pushToStreams->contains($stream) && $stream->processExternalPush($this);
    }
    
    protected function processExternalPush(Stream $sender): bool
    {
        $this->extraItems[] = $sender->signal->item;
    
        return $this->continueIteration(true);
    }
    
    private function finishSubstreems()
    {
        if ($this->pushToStreams !== null) {
            while ($this->pushToStreams->count() > 0) {
                foreach ($this->pushToStreams as $stream) {
                    if (!$stream->continueIteration(true)) {
                        $this->pushToStreams->detach($stream);
                    }
                }
            }
        }
    }
}