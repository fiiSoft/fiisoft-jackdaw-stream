<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw;

use FiiSoft\Jackdaw\Collector\Collector;
use FiiSoft\Jackdaw\Comparator\{ComparatorReady, Sorting\By, Sorting\Sorting};
use FiiSoft\Jackdaw\Consumer\{ConsumerReady, Consumers};
use FiiSoft\Jackdaw\Discriminator\{DiscriminatorReady, Discriminators};
use FiiSoft\Jackdaw\Exception\{InvalidParamException, StreamExceptionFactory};
use FiiSoft\Jackdaw\Filter\{FilterReady, Filters};
use FiiSoft\Jackdaw\Handler\{ErrorHandler, OnError};
use FiiSoft\Jackdaw\Internal\{Check, Collection\BaseStreamCollection, Destroyable, Executable, Helper, Item,
    Iterator\Interruption, Mode, Pipe, Signal, State\Source, State\SourceData, State\SourceNotReady, State\Sources,
    State\StreamSource, StreamPipe};
use FiiSoft\Jackdaw\Mapper\{Internal\ConditionalExtract, MapperReady, Mappers};
use FiiSoft\Jackdaw\Memo\MemoWriter;
use FiiSoft\Jackdaw\Operation\Internal\{DispatchReady, ForkReady, Operations};
use FiiSoft\Jackdaw\Operation\LastOperation;
use FiiSoft\Jackdaw\Operation\Operation;
use FiiSoft\Jackdaw\Operation\Special\{Assert\AssertionFailed, Iterate};
use FiiSoft\Jackdaw\Operation\Terminating\FinalOperation;
use FiiSoft\Jackdaw\Producer\{Internal\EmptyProducer, MultiProducer, Producer, ProducerReady, Producers};
use FiiSoft\Jackdaw\Reducer\Reducer;
use FiiSoft\Jackdaw\ValueRef\IntProvider;

/**
 * @implements \IteratorAggregate<string|int, mixed>
 */
final class Stream extends StreamSource
    implements ProducerReady, DispatchReady, Executable, Destroyable, \IteratorAggregate
{
    private Producer $producer;
    private Sources $sources;
    private Source $source;
    private Signal $signal;
    private Pipe $pipe;
    
    /** @var Stream[] */
    private array $parents = [];
    
    private bool $isExecuted = false;
    private bool $isLoop = false;
    private bool $isFirstProducer = true;
    private bool $isDestroying = false;
    private bool $isInitialized = false;
    private bool $isStarted = false;
    private bool $isResuming = false;
    private bool $streamingFinished = false;
    private bool $isConsumer = false;
    private bool $isPrototype = false;
    
    /** @var StreamPipe[] */
    private array $pushToStreams = [];
    
    /** @var callable[] */
    private array $onFinishHandlers = [];
    
    /** @var callable[] */
    private array $onSuccessHandlers = [];
    
    /** @var ErrorHandler[] */
    private array $onErrorHandlers = [];
    
    /** @var array<array<ProducerReady|resource|callable|iterable<string|int, mixed>|string>> */
    private array $joinProducers = [];
    
    /**
     * @param ProducerReady|\Traversable<mixed>|resource|callable|iterable<mixed>|object|scalar ...$elements
     */
    public static function of(...$elements): Stream
    {
        return self::from(Producers::from($elements));
    }
    
    /**
     * @param ProducerReady|\Traversable<mixed>|resource|callable|iterable<mixed>|string $producer
     */
    public static function from($producer): Stream
    {
        return new self(Producers::getAdapter($producer));
    }
    
    public static function empty(): Stream
    {
        return new self(new EmptyProducer());
    }
    
    /**
     * Experimental. Returns immutable Stream object appropriate to build various streams based on prototype.
     * Any Producers, Consumers, etc. are shared by related streams build on top of prototype instances.
     * I'm pretty sure it's not well tested yet. A bit risky, but it offers some new, nice possibilities.
     *
     * @param ProducerReady|\Traversable<mixed>|resource|callable|iterable<mixed>|string|null $producer
     */
    public static function prototype($producer = null): Stream
    {
        $prototype = $producer !== null ? self::from($producer) : self::empty();
        $prototype->isPrototype = true;
        
        return $prototype;
    }
    
    private function __construct(Producer $producer)
    {
        $this->producer = $producer;
        $this->pipe = new Pipe($this);
    }
    
    protected function __clone()
    {
        if ($this->isInitialized || $this->isExecuted) {
            throw StreamExceptionFactory::cannotReuseUtilizedStream();
        }
        
        $this->pipe = clone $this->pipe;
        $this->pipe->head->assignStream($this);
    }
    
    protected function cloneStream(): Stream
    {
        $copy = clone $this;
        $copy->prepareForFork();
        
        return $copy;
    }
    
    private function prepareForFork(): void
    {
        $this->resetState();
        
        $this->isInitialized = false;
        $this->isExecuted = false;
        $this->isStarted = false;
        $this->isConsumer = false;
        
        $this->pipe->prepare();
        $this->producer = MultiProducer::oneTime();
        
        $this->initialize();
    }
    
    public function limit(int $limit): Stream
    {
        return $this->addOperation(Operations::limit($limit));
    }
    
    /**
     * @param IntProvider|callable|int $offset
     */
    public function skip($offset): Stream
    {
        return $this->addOperation(Operations::skip($offset));
    }
    
    /**
     * @param FilterReady|callable|array<string|int, mixed>|scalar $filter
     */
    public function skipWhile($filter, ?int $mode = null): Stream
    {
        return $this->addOperation(Operations::skipWhile($filter, $mode));
    }
    
    /**
     * @param FilterReady|callable|array<string|int, mixed>|scalar $filter
     */
    public function skipUntil($filter, ?int $mode = null): Stream
    {
        return $this->addOperation(Operations::skipUntil($filter, $mode));
    }
    
    /**
     * Filters out null values
     */
    public function notNull(int $mode = Check::VALUE): Stream
    {
        return $this->filter(Filters::notNull($mode));
    }
    
    /**
     * Filters out empty values
     */
    public function notEmpty(int $mode = Check::VALUE): Stream
    {
        return $this->filter(Filters::notEmpty($mode));
    }
    
    /**
     * @param array<mixed> $values
     */
    public function without(array $values, int $mode = Check::VALUE): Stream
    {
        if (\count($values) === 1) {
            $filter = Filters::same($values[\array_key_first($values)], $mode);
        } else {
            $filter = Filters::onlyIn($values, $mode);
        }
        
        return $this->omit($filter);
    }
    
    /**
     * @param array<mixed> $values
     */
    public function only(array $values, int $mode = Check::VALUE): Stream
    {
        if (\count($values) === 1) {
            $filter = Filters::same($values[\array_key_first($values)], $mode);
        } else {
            $filter = Filters::onlyIn($values, $mode);
        }
        
        return $this->filter($filter);
    }
    
    /**
     * It only passes array (or \ArrayAccess) values containing the specified field(s).
     *
     * @param array<string|int>|string|int $fields
     */
    public function onlyWith($fields, bool $allowNulls = false): Stream
    {
        return $this->filter(Filters::onlyWith($fields, $allowNulls));
    }
    
    /**
     * @param float|int $value
     */
    public function greaterThan($value, int $mode = Check::VALUE): Stream
    {
        return $this->filter(Filters::greaterThan($value, $mode));
    }
    
    /**
     * @param float|int $value
     */
    public function greaterOrEqual($value, int $mode = Check::VALUE): Stream
    {
        return $this->filter(Filters::greaterOrEqual($value, $mode));
    }
    
    /**
     * @param float|int $value
     */
    public function lessThan($value, int $mode = Check::VALUE): Stream
    {
        return $this->filter(Filters::lessThan($value, $mode));
    }
    
    /**
     * @param float|int $value
     */
    public function lessOrEqual($value, int $mode = Check::VALUE): Stream
    {
        return $this->filter(Filters::lessOrEqual($value, $mode));
    }
    
    /**
     * Filters out non-numeric values
     */
    public function onlyNumeric(int $mode = Check::VALUE): Stream
    {
        return $this->filter(Filters::isNumeric($mode));
    }
    
    /**
     * Filters out non-integer values
     */
    public function onlyIntegers(int $mode = Check::VALUE): Stream
    {
        return $this->filter(Filters::isInt($mode));
    }
    
    /**
     * Filters out non-string values
     */
    public function onlyStrings(int $mode = Check::VALUE): Stream
    {
        return $this->filter(Filters::isString($mode));
    }
    
    /**
     * Assert that element in stream satisfies given requirements.
     * If not, it throws non-catchable exception.
     *
     * @param FilterReady|callable|array<string|int, mixed>|scalar $filter
     * @throws AssertionFailed
     */
    public function assert($filter, ?int $mode = null): Stream
    {
        return $this->addOperation(Operations::assert($filter, $mode));
    }
    
    /**
     * Alias for map('trim').
     */
    public function trim(): Stream
    {
        return $this->map(Mappers::trim());
    }
    
    /**
     * @param string|int $field
     * @param FilterReady|callable|array<string|int, mixed>|scalar $filter
     */
    public function filterBy($field, $filter): Stream
    {
        return $this->addOperation(Operations::filterBy($field, $filter));
    }
    
    /**
     * @param FilterReady|callable|array<string|int, mixed>|scalar $filter
     */
    public function filter($filter, ?int $mode = null): Stream
    {
        return $this->addOperation(Operations::filter($filter, $mode));
    }
    
    /**
     * @param FilterReady|callable|array<string|int, mixed>|scalar $condition
     * @param FilterReady|callable|array<string|int, mixed>|scalar $filter
     */
    public function filterWhen($condition, $filter, ?int $mode = null): Stream
    {
        return $this->addOperation(Operations::filterWhen($condition, $filter, $mode));
    }
    
    /**
     * @param FilterReady|callable|array<string|int, mixed>|scalar $condition
     * @param FilterReady|callable|array<string|int, mixed>|scalar $filter
     */
    public function filterWhile($condition, $filter, ?int $mode = null): Stream
    {
        return $this->addOperation(Operations::filterWhile($condition, $filter, $mode));
    }
    
    /**
     * @param FilterReady|callable|array<string|int, mixed>|scalar $condition
     * @param FilterReady|callable|array<string|int, mixed>|scalar $filter
     */
    public function filterUntil($condition, $filter, ?int $mode = null): Stream
    {
        return $this->addOperation(Operations::filterUntil($condition, $filter, $mode));
    }
    
    public function filterArgs(callable $filter): Stream
    {
        return $this->addOperation(Operations::filterArgs($filter));
    }
    
    /**
     * @param FilterReady|callable|array<string|int, mixed>|scalar $filter
     */
    public function filterKey($filter): Stream
    {
        return $this->filter($filter, Check::KEY);
    }
    
    /**
     * @param string|int $field
     * @param FilterReady|callable|array<string|int, mixed>|scalar $filter
     */
    public function omitBy($field, $filter): Stream
    {
        return $this->addOperation(Operations::omitBy($field, $filter));
    }
    
    /**
     * @param FilterReady|callable|array<string|int, mixed>|scalar $filter
     */
    public function omit($filter, ?int $mode = null): Stream
    {
        return $this->addOperation(Operations::omit($filter, $mode));
    }
    
    /**
     * @param FilterReady|callable|array<string|int, mixed>|scalar $condition
     * @param FilterReady|callable|array<string|int, mixed>|scalar $filter
     */
    public function omitWhen($condition, $filter, ?int $mode = null): Stream
    {
        return $this->addOperation(Operations::omitWhen($condition, $filter, $mode));
    }
    
    /**
     * This operation skips all repeatable consecutive values in series, so each value is different than previous one.
     * Unlike Unique, values can repeat in whole stream, but not in succession.
     *
     * @param ComparatorReady|callable|null $comparison
     */
    public function omitReps($comparison = null): Stream
    {
        return $this->addOperation(Operations::omitReps($comparison));
    }
    
    /**
     * @param FilterReady|callable|array<string|int, mixed>|scalar $filter
     */
    public function omitKey($filter): Stream
    {
        return $this->omit($filter, Check::KEY);
    }
    
    /**
     * @param array<string|int>|string|int|null $fields
     */
    public function castToInt($fields = null): Stream
    {
        return $this->map(Mappers::toInt($fields));
    }
    
    /**
     * @param array<string|int>|string|int|null $fields
     */
    public function castToFloat($fields = null): Stream
    {
        return $this->map(Mappers::toFloat($fields));
    }
    
    /**
     * @param array<string|int>|string|int|null $fields
     */
    public function castToString($fields = null): Stream
    {
        return $this->map(Mappers::toString($fields));
    }
    
    /**
     * @param array<string|int>|string|int|null $fields
     */
    public function castToBool($fields = null): Stream
    {
        return $this->map(Mappers::toBool($fields));
    }
    
    /**
     * @param array<string|int>|string|int|null $fields
     * @param \DateTimeZone|string|null $inTimeZone
     */
    public function castToTime($fields = null, ?string $fromFormat = null, $inTimeZone = null): Stream
    {
        return $this->map(Mappers::toTime($fields, $fromFormat, $inTimeZone));
    }
    
    /**
     * It works exactly the same way as remap, it is only different syntax to use.
     * Processed value have to be an array.
     *
     * @param string|int $before old name of key
     * @param string|int $after new name of key
     */
    public function rename($before, $after): Stream
    {
        return $this->remap([$before => $after]);
    }
    
    /**
     * Change names of keys in array-like values.
     * New keys are added at the end of existing array, and old keys are deleted.
     *
     * @param array<string|int> $keys map names of keys to rename (before => after)
     */
    public function remap(array $keys): Stream
    {
        return $this->map(Mappers::remap($keys));
    }
    
    /**
     * Syntax sugar to reorder keys in array-like values by extracting them.
     *
     * @param array<string|int> $keys
     */
    public function reorder(array $keys): Stream
    {
        return $this->map(Mappers::reorderKeys($keys));
    }
    
    /**
     * @param MapperReady|callable|iterable|mixed $mapper
     */
    public function map($mapper): Stream
    {
        return $this->addOperation(Operations::map($mapper));
    }
    
    /**
     * @param FilterReady|callable|array<string|int, mixed>|scalar $condition
     * @param MapperReady|callable|iterable|mixed $mapper
     * @param MapperReady|callable|iterable|mixed|null $elseMapper
     */
    public function mapWhen($condition, $mapper, $elseMapper = null): Stream
    {
        return $this->addOperation(Operations::mapWhen($condition, $mapper, $elseMapper));
    }
    
    /**
     * @param string|int $field
     * @param MapperReady|callable|iterable|mixed $mapper
     */
    public function mapField($field, $mapper): Stream
    {
        return $this->map(Mappers::mapField($field, $mapper));
    }
    
    /**
     * @param string|int $field
     * @param FilterReady|callable|array<string|int, mixed>|scalar $condition
     * @param MapperReady|callable|iterable|mixed $mapper
     * @param MapperReady|callable|iterable|mixed|null $elseMapper
     */
    public function mapFieldWhen($field, $condition, $mapper, $elseMapper = null): Stream
    {
        return $this->addOperation(Operations::mapFieldWhen($field, $condition, $mapper, $elseMapper));
    }
    
    /**
     * @param FilterReady|callable|array<string|int, mixed>|scalar $condition
     * @param MapperReady|callable|iterable|mixed $mapper
     */
    public function mapWhile($condition, $mapper): Stream
    {
        return $this->addOperation(Operations::mapWhile($condition, $mapper));
    }
    
    /**
     * @param FilterReady|callable|array<string|int, mixed>|scalar $condition
     * @param MapperReady|callable|iterable|mixed $mapper
     */
    public function mapUntil($condition, $mapper): Stream
    {
        return $this->addOperation(Operations::mapUntil($condition, $mapper));
    }
    
    /**
     * @param DiscriminatorReady|callable|array<string|int> $discriminator
     * @param array<string|int, MapperReady|callable|iterable|mixed> $mappers
     */
    public function mapBy($discriminator, array $mappers): Stream
    {
        return $this->addOperation(Operations::mapBy($discriminator, $mappers));
    }
    
    /**
     * Syntax sugar for $stream->mapBy(Discriminators::byKey(), $mappers)
     *
     * @param array<string|int, MapperReady|callable|iterable|mixed> $mappers
     */
    public function mapByKey(array $mappers): Stream
    {
        return $this->mapBy(Discriminators::byKey(), $mappers);
    }
    
    public function mapArgs(callable $mapper): Stream
    {
        return $this->addOperation(Operations::mapArgs($mapper));
    }
    
    /**
     * It works very similarly to mapKey - the difference is that it uses Discriminator as mapper
     * and guarantees that key is string, int or bool.
     *
     * @param DiscriminatorReady|callable|array<string|int> $discriminator
     */
    public function classify($discriminator): Stream
    {
        return $this->addOperation(Operations::classify($discriminator));
    }
    
    /**
     * @param string|int $field
     * @param string|int|null $orElse
     */
    public function classifyBy($field, $orElse = null): Stream
    {
        return $this->classify(Discriminators::byField($field, $orElse));
    }
    
    /**
     * Allows key mapping. If a string (but not callable) or an int is given, exactly that value is set as key.
     *
     * @param MapperReady|callable|iterable|mixed $mapper
     */
    public function mapKey($mapper): Stream
    {
        return $this->addOperation(Operations::mapKey($mapper));
    }
    
    /**
     * This is specialized map operation which maps both key and value at the same time.
     * Callable $factory can accept zero, one (value) or two (value, key) params and MUST return array
     * with exactly one element - new pair of [key => value] passed to next step in stream.
     */
    public function mapKV(callable $keyValueMapper): Stream
    {
        return $this->addOperation(Operations::mapKeyValue($keyValueMapper));
    }
    
    /**
     * Each time the signal reaches this operation, the value of passed variable is increased by 1.
     *
     * @param int|null $counter REFERENCE is set to 0 when NULL during initialization
     */
    public function countIn(?int &$counter): Stream
    {
        return $this->addOperation(Operations::countIn($counter));
    }
    
    /**
     * It works in a similar way to method collectIn() and allows to store values with keys
     * in given array $buffer instead of Collector.
     *
     * @param \ArrayAccess<string|int, mixed>|array<string|int, mixed> $buffer REFERENCE
     */
    public function storeIn(&$buffer, bool $reindex = false): Stream
    {
        return $this->addOperation(Operations::storeIn($buffer, $reindex));
    }
    
    /**
     * @param Collector|\ArrayAccess<string|int, mixed>|\SplHeap<mixed>|\SplPriorityQueue<int, mixed> $collector
    */
    public function collectIn($collector, ?bool $reindex = null): Stream
    {
        return $this->addOperation(Operations::collectIn($collector, $reindex));
    }
    
    /**
     * @param Collector|\ArrayAccess<string|int, mixed>|\SplHeap<mixed>|\SplPriorityQueue<int, mixed> $collector
     */
    public function collectKeysIn($collector): Stream
    {
        return $this->addOperation(Operations::collectKeysIn($collector));
    }
    
    /**
     * @param ConsumerReady|callable|resource $consumers resource must be writeable
     */
    public function call(...$consumers): Stream
    {
        return $this->addOperation(Operations::call(...$consumers));
    }
    
    /**
     * @param ConsumerReady|callable|resource $consumer
     */
    public function callOnce($consumer): Stream
    {
        return $this->callMax(1, $consumer);
    }
    
    /**
     * @param ConsumerReady|callable|resource $consumer
     */
    public function callMax(int $times, $consumer): Stream
    {
        return $this->addOperation(Operations::callMax($times, $consumer));
    }
    
    /**
     * @param FilterReady|callable|array<string|int, mixed>|scalar $condition
     * @param ConsumerReady|callable|resource $consumer
     * @param ConsumerReady|callable|resource|null $elseConsumer
     */
    public function callWhen($condition, $consumer, $elseConsumer = null): Stream
    {
        return $this->addOperation(Operations::callWhen($condition, $consumer, $elseConsumer));
    }
    
    /**
     * @param FilterReady|callable|array<string|int, mixed>|scalar $condition
     * @param ConsumerReady|callable|resource $consumer
     */
    public function callWhile($condition, $consumer): Stream
    {
        return $this->addOperation(Operations::callWhile($condition, $consumer));
    }
    
    /**
     * @param FilterReady|callable|array<string|int, mixed>|scalar $condition
     * @param ConsumerReady|callable|resource $consumer
     */
    public function callUntil($condition, $consumer): Stream
    {
        return $this->addOperation(Operations::callUntil($condition, $consumer));
    }
    
    public function callArgs(callable $consumer): Stream
    {
        return $this->addOperation(Operations::callArgs($consumer));
    }
    
    /**
     * Sugar syntax to store current value or key in some external variable.
     *
     * @param mixed $variable REFERENCE
     */
    public function putIn(&$variable, int $mode = Check::VALUE): Stream
    {
        $mode = Mode::get($mode);
        
        if ($mode === Check::VALUE) {
            return $this->call(Consumers::sendValueTo($variable));
        }
        
        if ($mode === Check::KEY) {
            return $this->call(Consumers::sendKeyTo($variable));
        }
        
        throw StreamExceptionFactory::invalidModeForPutInOperation();
    }
    
    /**
     * Sugar syntax to store current value and key in external variables.
     *
     * @param mixed $value REFERENCE
     * @param mixed $key REFERENCE
     */
    public function putValueKeyIn(&$value, &$key): Stream
    {
        return $this->call(Consumers::sendValueKeyTo($value, $key));
    }
    
    /**
     * The name of this method is very misleading. It should be called: addProducers().
     * It doesn't matter where in the build stream this is called - it simply adds new producers to the stream.
     *
     * @param ProducerReady|resource|callable|iterable<string|int, mixed>|string ...$producers
     */
    public function join(...$producers): Stream
    {
        if ($this->isPrototype) {
            $copy = clone $this;
            $copy->joinProducers[] = $producers;
         
            return $copy;
        }

        $this->initialize();
        $this->source->addProducers($producers);
        
        return $this;
    }
    
    /**
     * @param ComparatorReady|callable|null $comparison
     */
    public function unique($comparison = null): Stream
    {
        return $this->addOperation(Operations::unique($comparison));
    }
    
    /**
     * @param string|int ...$fields field(s) to sort by, e.g. "name asc", "salary desc", 0, 3, "1 asc", "3 desc"
     */
    public function sortBy(...$fields): Stream
    {
        return $this->sort(By::fields($fields));
    }
    
    /**
     * Normal (ascending) sorting.
     *
     * @param ComparatorReady|callable|null $sorting
     */
    public function sort($sorting = null): Stream
    {
        return $this->addOperation(Operations::sort($sorting));
    }
    
    /**
     * Reversed (descending) sorting.
     *
     * @param ComparatorReady|callable|null $sorting
     */
    public function rsort($sorting = null): Stream
    {
        return $this->sort(Sorting::reverse($sorting));
    }
    
    /**
     * Normal sorting with limited number of {$limit} first values passed further to stream.
     *
     * @param ComparatorReady|callable|null $sorting
     */
    public function best(int $limit, $sorting = null): Stream
    {
        return $this->addOperation(Operations::sortLimited($limit, $sorting));
    }
    
    /**
     * Reversed sorting with limited number of {$limit} values passed further to stream.
     *
     * @param ComparatorReady|callable|null $sorting
     */
    public function worst(int $limit, $sorting = null): Stream
    {
        return $this->best($limit, Sorting::reverse($sorting));
    }
    
    /**
     * Collect all incoming elements from stream and when there are no more elements,
     * reverse their order and start streaming again.
     */
    public function reverse(): Stream
    {
        return $this->addOperation(Operations::reverse());
    }
    
    /**
     * Collect all incoming elements from stream and when there are no more elements,
     * start streaming them again in randomized order.
     *
     * @param int|null $chunkSize when > 1 it collects and shuffles chunks of data
     */
    public function shuffle(?int $chunkSize = null): Stream
    {
        return $this->addOperation(Operations::shuffle($chunkSize));
    }
    
    /**
     * Reindex all keys for elements (0, 1, ...).
     * Optionally, it can start from different value and with different step (step cannot be 0).
     *
     * @param int $start initial value
     * @param int $step change value
     */
    public function reindex(int $start = 0, int $step = 1): Stream
    {
        return $this->addOperation(Operations::reindex($start, $step));
    }
    
    /**
     * Reindex keys with values from field of arrays.
     *
     * @param string|int $field
     * @param bool $move when true then field will be removed from value
     */
    public function reindexBy($field, bool $move = false): Stream
    {
        $keyExtractor = Mappers::fieldValue($field);
        
        if ($move) {
            $fieldRemover = Mappers::remove($field);
            
            return $this->mapKV(static fn($value, $key): array => [
                $keyExtractor->map($value, $key) => $fieldRemover->map($value, $key)
            ]);
        }
        
        return $this->mapKey($keyExtractor);
    }
    
    /**
     * Flips values with keys
     */
    public function flip(): Stream
    {
        return $this->addOperation(Operations::flip());
    }
    
    /**
     * @param mixed $initial
     * @param Reducer|callable $reducer
     */
    public function scan($initial, $reducer): Stream
    {
        return $this->addOperation(Operations::scan($initial, $reducer));
    }
    
    /**
     * @param IntProvider|\Traversable<int>|iterable<int>|callable|int $size
     */
    public function chunk($size, bool $reindex = false): Stream
    {
        return $this->addOperation(Operations::chunk($size, $reindex));
    }
    
    /**
     * @param DiscriminatorReady|callable|array<string|int>|string|int $discriminator
     */
    public function chunkBy($discriminator, ?bool $reindex = null): Stream
    {
        return $this->addOperation(Operations::chunkBy($discriminator, $reindex));
    }
    
    /**
     * Syntax sugar for $stream->chunkBy(Discriminators::byKey())
     */
    public function chunkByKey(?bool $reindex = null): Stream
    {
        return $this->chunkBy(Discriminators::byKey(), $reindex);
    }
    
    public function window(int $size, int $step = 1, bool $reindex = false): Stream
    {
        return $this->addOperation(Operations::window($size, $step, $reindex));
    }
    
    public function everyNth(int $num): Stream
    {
        return $this->addOperation(Operations::everyNth($num));
    }
    
    public function skipNth(int $num): Stream
    {
        return $this->addOperation(Operations::skipNth($num));
    }
    
    /**
     * @param FilterReady|callable|array<string|int, mixed>|scalar $filter
     */
    public function accumulate($filter, bool $reindex = false, ?int $mode = null): Stream
    {
        return $this->addOperation(Operations::accumulate($filter, $reindex, $mode));
    }
    
    /**
     * @param FilterReady|callable|array<string|int, mixed>|scalar $filter
     */
    public function separateBy($filter, bool $reindex = false, ?int $mode = null): Stream
    {
        return $this->addOperation(Operations::separateBy($filter, $reindex, $mode));
    }
    
    /**
     * @param array<string|int> $keys
     */
    public function aggregate(array $keys): Stream
    {
        return $this->addOperation(Operations::aggregate($keys));
    }
    
    /**
     * @param string|int $field
     * @param MapperReady|callable|iterable|mixed $mapper
     */
    public function append($field, $mapper): Stream
    {
        return $this->map(Mappers::append($field, $mapper));
    }
    
    /**
     * @param string|int $field
     * @param MapperReady|callable|iterable|mixed $mapper
     */
    public function complete($field, $mapper): Stream
    {
        return $this->map(Mappers::complete($field, $mapper));
    }
    
    /**
     * @param string|int $field
     * @param string|int|null $key
     */
    public function moveTo($field, $key = null): Stream
    {
        return $this->map(Mappers::moveTo($field, $key));
    }
    
    /**
     * @param array<string|int>|string|int $fields
     * @param mixed|null $orElse
     */
    public function extract($fields, $orElse = null): Stream
    {
        return $this->map(Mappers::extract($fields, $orElse));
    }
    
    /**
     * @param FilterReady|callable|array<string|int, mixed>|scalar $filter
     */
    public function extractWhen($filter, ?int $mode = null): Stream
    {
        return $this->map(new ConditionalExtract($filter, $mode));
    }
    
    /**
     * @param array<string|int>|string|int ...$fields
     */
    public function remove(...$fields): Stream
    {
        if (\count($fields) === 1 && \is_array($fields[0] ?? null)) {
            $fields = $fields[0];
        }
        
        return $this->map(Mappers::remove($fields));
    }
    
    /**
     * @param FilterReady|callable|array<string|int, mixed>|scalar $filter
     */
    public function removeWhen($filter, ?int $mode = null): Stream
    {
        return $this->map(new ConditionalExtract($filter, $mode, true));
    }
    
    public function split(string $separator = ' '): Stream
    {
        return $this->map(Mappers::split($separator));
    }
    
    public function concat(string $separtor = ' '): Stream
    {
        return $this->map(Mappers::concat($separtor));
    }
    
    public function tokenize(string $tokens = ' '): Stream
    {
        return $this->addOperation(Operations::tokenize($tokens));
    }
    
    public function flat(int $level = 0): Stream
    {
        return $this->addOperation(Operations::flat($level));
    }
    
    /**
     * @param MapperReady|callable|iterable|mixed $mapper
     */
    public function flatMap($mapper, int $level = 0): Stream
    {
        return $this->map($mapper)->flat($level);
    }
    
    /**
     * The $producer callable can take zero, one (value), or two (value, key) parameters and MUST return an iterable
     * which elements will be passed on to the stream. In other words, it's kind of flatMap operation, but it can be
     * much more robust and memory effective when Generator is used to produce consecutive values.
     */
    public function iterate(callable $producer): Stream
    {
        return $this->addOperation(Operations::iterateOver($producer));
    }
    
    /**
     * @param Stream|LastOperation ...$streams
     */
    public function feed(StreamPipe ...$streams): Stream
    {
        $instance = $this->instance();
        
        foreach ($streams as $stream) {
            $instance->registerFeedStream($stream);
        }
        
        $instance->chainOperation(Operations::feed(...$streams));
        
        return $instance;
    }
    
    /**
     * @param DiscriminatorReady|callable|array<string|int> $discriminator
     * @param DispatchReady[] $handlers
     */
    public function dispatch($discriminator, array $handlers): Stream
    {
        foreach ($handlers as $handler) {
            if ($handler === $this) {
                throw StreamExceptionFactory::dispatchOperationCannotHandleLoops();
            }
        }
        
        return $this->addOperation(Operations::dispatch($discriminator, $handlers));
    }
    
    /**
     * @param FilterReady|callable|array<string|int, mixed>|scalar $condition
     */
    public function route($condition, DispatchReady $handler): Stream
    {
        return $this->addOperation(Operations::route($condition, $handler));
    }
    
    /**
     * @param DiscriminatorReady|callable|array<string|int> $discriminator
     * @param DispatchReady[] $handlers
     */
    public function switch($discriminator, array $handlers): Stream
    {
        return $this->addOperation(Operations::switch($discriminator, $handlers));
    }
    
    /**
     * It works like conditional limit().
     *
     * @param FilterReady|callable|array<string|int, mixed>|scalar $filter
     */
    public function while($filter, ?int $mode = null): Stream
    {
        return $this->addOperation(Operations::while($filter, $mode));
    }
    
    /**
     * It works like conditional limit().
     *
     * @param FilterReady|callable|array<string|int, mixed>|scalar $filter
     */
    public function until($filter, ?int $mode = null): Stream
    {
        return $this->addOperation(Operations::until($filter, $mode));
    }
    
    /**
     * @param int $numOfItems number of Nth last elements
     */
    public function tail(int $numOfItems): Stream
    {
        return $this->addOperation(Operations::tail($numOfItems));
    }
    
    /**
     * It works similar to chunk, but it gathers all elements until stream is empty,
     * and then passes whole array as argument for next step.
     *
     * @param bool $reindex
     */
    public function gather(bool $reindex = false): Stream
    {
        return $this->addOperation(Operations::gather($reindex));
    }
    
    /**
     * It collects elements in array as long as they meet given condition.
     * With first element which does not meet condition, gathering values is aborted
     * and array of collected elements is passed to next step. No other items in the stream will be read.
     *
     * @param FilterReady|callable|array<string|int, mixed>|scalar $filter
     */
    public function gatherWhile($filter, bool $reindex = false, ?int $mode = null): Stream
    {
        return $this->while($filter, $mode)->gather($reindex);
    }
    
    /**
     * It collects elements in array until first element which does not meet given condition,
     * in which case gathering of values is aborted and array of collected elements is passed to next step.
     * No other items in the stream will be read.
     *
     * @param FilterReady|callable|array<string|int, mixed>|scalar $filter
     */
    public function gatherUntil($filter, bool $reindex = false, ?int $mode = null): Stream
    {
        return $this->until($filter, $mode)->gather($reindex);
    }
    
    /**
     * @param int|null $buckets null means collect all elements
     * @param ComparatorReady|callable|null $comparison
     * @param int|null $limit max number of collected elements in each bucket; null means no limits
     */
    public function segregate(
        ?int $buckets = null, bool $reindex = false, $comparison = null, ?int $limit = null
    ): Stream
    {
        return $this->addOperation(Operations::segregate($buckets, $reindex, $comparison, $limit));
    }
    
    /**
     * @param DiscriminatorReady|callable|array<string|int>|string|int $discriminator
     */
    public function categorize($discriminator, ?bool $reindex = null): Stream
    {
        return $this->addOperation(Operations::categorize($discriminator, $reindex));
    }
    
    /**
     * @param string|int $field
     */
    public function categorizeBy($field, ?bool $reindex = null): Stream
    {
        return $this->categorize(Discriminators::byField($field), $reindex);
    }
    
    /**
     * Syntax sugar for $stream->categorize(Discriminators::byKey())
     */
    public function categorizeByKey(?bool $reindex = null): Stream
    {
        return $this->categorize(Discriminators::byKey(), $reindex);
    }
    
    /**
     * Replace value of current element with its [key, value].
     * When param $assoc is true then it creates pair ['key' => key, 'value' => value].
     * In both cases, real key of element is reindexed starting from 0 (like in reindex() operation).
     */
    public function makeTuple(bool $assoc = false): Stream
    {
        return $this->addOperation(Operations::makeTuple($assoc));
    }
    
    /**
     * This operation works in the opposite way to makeTuple() - it maps tuple from the value
     * ([key, value] or ['key' => key, 'value' => value]) to key and value of current element.
     */
    public function unpackTuple(bool $assoc = false): Stream
    {
        return $this->addOperation(Operations::unpackTuple($assoc));
    }
    
    /**
     * Creates a numeric array where the first item comes from this stream, and the next items come from all passed
     * providers of subsequent values. In the event that any supplier runs out, null is inserted in its place.
     *
     * @param array<ProducerReady|resource|callable|iterable<string|int, mixed>|scalar> $sources
     */
    public function zip(...$sources): Stream
    {
        return $this->addOperation(Operations::zip(...$sources));
    }
    
    public function unzip(DispatchReady ...$consumers): Stream
    {
        return $this->addOperation(Operations::unzip(...$consumers));
    }
    
    /**
     * @param DiscriminatorReady|callable|array<string|int> $discriminator
     */
    public function fork($discriminator, ForkReady $prototype): Stream
    {
        return $this->addOperation(Operations::fork($discriminator, $prototype));
    }
    
    /**
     * @param string|int $field
     */
    public function forkBy($field, ForkReady $prototype): Stream
    {
        return $this->fork(Discriminators::byField($field), $prototype);
    }
    
    /**
     * Syntax sugar for $stream->fork(Discriminators::byKey(), $prototype)
     */
    public function forkByKey(ForkReady $prototype): Stream
    {
        return $this->fork(Discriminators::byKey(), $prototype);
    }
    
    /**
     * @param DiscriminatorReady|callable|array<string|int> $discriminator
     * @param array<string|int, ForkReady> $handlers
     */
    public function forkMatch($discriminator, array $handlers, ?ForkReady $prototype = null): Stream
    {
        return $this->addOperation(Operations::forkMatch($discriminator, $handlers, $prototype));
    }
    
    /**
     * Remember key and/or value of current element in passed writer.
     */
    public function remember(MemoWriter $memo): Stream
    {
        return $this->addOperation(Operations::remember($memo));
    }
    
    /**
     * @param ComparatorReady|callable|null $comparison
     */
    public function accumulateUptrends(bool $reindex = false, $comparison = null): Stream
    {
        return $this->addOperation(Operations::accumulateUptrends($reindex, $comparison));
    }
    
    /**
     * @param ComparatorReady|callable|null $comparison
     */
    public function accumulateDowntrends(bool $reindex = false, $comparison = null): Stream
    {
        return $this->addOperation(Operations::accumulateDowntrends($reindex, $comparison));
    }
    
    /**
     * @param bool $allowLimits when true then allow for limit values (first and last element in the stream)
     * @param ComparatorReady|callable|null $comparison
     */
    public function onlyMaxima(bool $allowLimits = true, $comparison = null): Stream
    {
        return $this->addOperation(Operations::onlyMaxima($allowLimits, $comparison));
    }
    
    /**
     * @param bool $allowLimits when true then allow for limit values (first and last element in the stream)
     * @param ComparatorReady|callable|null $comparison
     */
    public function onlyMinima(bool $allowLimits = true, $comparison = null): Stream
    {
        return $this->addOperation(Operations::onlyMinima($allowLimits, $comparison));
    }
    
    /**
     * @param bool $allowLimits when true then allow for limit values (first and last element in the stream)
     * @param ComparatorReady|callable|null $comparison
     */
    public function onlyExtrema(bool $allowLimits = true, $comparison = null): Stream
    {
        return $this->addOperation(Operations::onlyExtrema($allowLimits, $comparison));
    }
    
    /**
     * @param ComparatorReady|callable|null $comparison
     */
    public function increasingTrend($comparison = null): Stream
    {
        return $this->addOperation(Operations::increasingTrend($comparison));
    }
    
    /**
     * @param ComparatorReady|callable|null $comparison
     */
    public function decreasingTrend($comparison = null): Stream
    {
        return $this->addOperation(Operations::decreasingTrend($comparison));
    }
    
    /**
     * Create new stream from the current one and set provided Producer as source of data for it.
     *
     * @param ProducerReady|\Traversable<mixed>|resource|callable|iterable<mixed>|string $producer
     */
    public function wrap($producer): Stream
    {
        $copy = clone $this;
        $copy->producer = Producers::getAdapter($producer);
        
        return $copy;
    }
    
    /**
     * @param IntProvider|\Traversable<int>|iterable<int>|callable|int $howMany how many elements should be read from
     *                                                                    the stream before passing the last one down
     */
    public function readNext($howMany = 1): Stream
    {
        return $this->addOperation(Operations::readNext($howMany));
    }
    
    /**
     * @param IntProvider|\Traversable<int>|iterable<int>|callable|int $howMany how many elements should be read
     *                                                        from the stream; every read element will be passed down
     */
    public function readMany($howMany, bool $reindex = false): Stream
    {
        return $this->addOperation(Operations::readMany($howMany, $reindex));
    }
    
    /**
     * @param FilterReady|callable|array<string|int, mixed>|scalar $filter
     * @param ConsumerReady|callable|resource|null $consumer resource must be writeable
     */
    public function readWhile($filter, ?int $mode = null, bool $reindex = false, $consumer = null): Stream
    {
        return $this->addOperation(Operations::readWhile($filter, $mode, $reindex, $consumer));
    }
    
    /**
     * @param FilterReady|callable|array<string|int, mixed>|scalar $filter
     * @param ConsumerReady|callable|resource|null $consumer resource must be writeable
     */
    public function readUntil($filter, ?int $mode = null, bool $reindex = false, $consumer = null): Stream
    {
        return $this->addOperation(Operations::readUntil($filter, $mode, $reindex, $consumer));
    }
    
    /**
     * Register handlers which will be called when error occurs.
     *
     * @param ErrorHandler|callable $handler it must return bool or null, see ErrorHandler
     * @param bool $replace when true then replace all existing handlers, when false then add handler to stack
     */
    public function onError($handler, bool $replace = false): Stream
    {
        if (\is_callable($handler)) {
            $handler = OnError::call($handler);
        }
        
        $instance = $this->instance();
        
        if ($handler instanceof ErrorHandler) {
            if ($replace) {
                $instance->onErrorHandlers = [$handler];
            } else {
                $instance->onErrorHandlers[] = $handler;
            }
        } else {
            throw InvalidParamException::describe('handler', $handler);
        }
        
        return $instance;
    }
    
    /**
     * Register handlers which will be called at the end and only when no errors occurred.
     *
     * @param callable $handler
     * @param bool $replace when true then replace all existing handlers, when false then add handler to stack
     */
    public function onSuccess(callable $handler, bool $replace = false): Stream
    {
        $instance = $this->instance();
        
        if ($replace) {
            $instance->onSuccessHandlers = [$handler];
        } else {
            $instance->onSuccessHandlers[] = $handler;
        }
        
        return $instance;
    }
    
    /**
     * Register handlers which will be called at the end and regardless any errors occurred or not,
     * but not in the case when uncaught exception has been thrown!
     *
     * @param callable $handler
     * @param bool $replace when true then replace all existing handlers, when false then add handler to stack
     */
    public function onFinish(callable $handler, bool $replace = false): Stream
    {
        $instance = $this->instance();
        
        if ($replace) {
            $instance->onFinishHandlers = [$handler];
        } else {
            $instance->onFinishHandlers[] = $handler;
        }
        
        return $instance;
    }
    
    /**
     * It works in the same way as toJson($flags, true).
     */
    public function toJsonAssoc(?int $flags = null): string
    {
        return $this->toJson($flags, true);
    }
    
    public function toJson(?int $flags = null, bool $preserveKeys = false): string
    {
        return \json_encode($this->toArray($preserveKeys), Helper::jsonFlags($flags));
    }
    
    public function toString(string $separator = ','): string
    {
        return \implode($separator, $this->toArray());
    }
    
    /**
     * It works in the same way as toArray(true).
     *
     * @return array<string|int, mixed>
     */
    public function toArrayAssoc(): array
    {
        return $this->toArray(true);
    }
    
    /**
     * @return array<string|int, mixed>
     */
    public function toArray(bool $preserveKeys = false): array
    {
        $operation = Operations::collectInArray($preserveKeys);
        $this->runWith($operation);

        return $operation->result();
    }
    
    /**
     * Collect all elements from stream.
     */
    public function collect(bool $reindex = false): LastOperation
    {
        return $this->addLastOperation(Operations::collect($this->instance(), $reindex));
    }
    
    /**
     * Collect all keys from stream.
     */
    public function collectKeys(): LastOperation
    {
        return $this->addLastOperation(Operations::collectKeys($this->instance()));
    }
    
    /**
     * Syntax sugar to collect values with keys indexed numerically.
     */
    public function collectValues(): LastOperation
    {
        return $this->collect(true);
    }
    
    /**
     * It collects data as long as the condition is true and then terminates processing when it is not.
     *
     * @param FilterReady|callable|array<string|int, mixed>|scalar $filter
     */
    public function collectWhile($filter, ?int $mode = null): LastOperation
    {
        return $this->while($filter, $mode)->collect();
    }
    
    /**
     * @param FilterReady|callable|array<string|int, mixed>|scalar $filter
     */
    public function collectUntil($filter, ?int $mode = null): LastOperation
    {
        return $this->until($filter, $mode)->collect();
    }
    
    /**
     * Count elements in stream.
     */
    public function count(): LastOperation
    {
        return $this->addLastOperation(Operations::count($this->instance()));
    }
    
    /**
     * @param Reducer|callable|array<Reducer|callable> $reducer
     * @param callable|mixed|null $orElse (default null)
     */
    public function reduce($reducer, $orElse = null): LastOperation
    {
        return $this->addLastOperation(Operations::reduce($this->instance(), $reducer, $orElse));
    }
    
    /**
     * @param mixed $initial
     * @param Reducer|callable $reducer Callable accepts two arguments: accumulator and current value
     */
    public function fold($initial, $reducer): LastOperation
    {
        return $this->addLastOperation(Operations::fold($this->instance(), $initial, $reducer));
    }
    
    /**
     * Tell if stream is not empty.
     */
    public function isNotEmpty(): LastOperation
    {
        return $this->addLastOperation(Operations::isNotEmpty($this->instance()));
    }
    
    /**
     * Tell if stream is empty.
     */
    public function isEmpty(): LastOperation
    {
        return $this->addLastOperation(Operations::isEmpty($this->instance()));
    }
    
    /**
     * Tell if element occurs in stream.
     *
     * @param FilterReady|callable|array<string|int, mixed>|scalar $value
     */
    public function has($value, ?int $mode = null): LastOperation
    {
        return $this->addLastOperation(Operations::has($this->instance(), $value, $mode));
    }
    
    /**
     * @param array<string|int, mixed> $values
     */
    public function hasAny(array $values, int $mode = Check::VALUE): LastOperation
    {
        return $this->has(Filters::onlyIn($values, $mode));
    }
    
    /**
     * @param array<string|int, mixed> $values
     */
    public function hasEvery(array $values, int $mode = Check::VALUE): LastOperation
    {
        return $this->addLastOperation(Operations::hasEvery($this->instance(), $values, $mode));
    }
    
    /**
     * @param array<string|int, mixed> $values
     */
    public function hasOnly(array $values, int $mode = Check::VALUE): LastOperation
    {
        return $this->addLastOperation(Operations::hasOnly($this->instance(), $values, $mode));
    }
    
    /**
     * Return first element in stream which satisfies given predicate or null when element was not found.
     *
     * @param FilterReady|callable|array<string|int, mixed>|scalar $predicate
     */
    public function find($predicate, ?int $mode = null): LastOperation
    {
        return $this->addLastOperation(Operations::find($this->instance(), $predicate, $mode));
    }
    
    /**
     * @param FilterReady|callable|array<string|int, mixed>|scalar $predicate
     */
    public function findMax(int $limit, $predicate, ?int $mode = null): LastOperation
    {
        return $this->filter($predicate, $mode)->limit($limit)->collect();
    }
    
    /**
     * Return first available element from stream or null when stream is empty.
     */
    public function first(): LastOperation
    {
        return $this->addLastOperation(Operations::first($this->instance()));
    }
    
    /**
     * Return first available element from stream or default when stream is empty.
     *
     * @param callable|mixed|null $orElse
     */
    public function firstOrElse($orElse): LastOperation
    {
        return $this->addLastOperation(Operations::first($this->instance(), $orElse));
    }
    
    /**
     * Return last element from stream or null when stream is empty.
     */
    public function last(): LastOperation
    {
        return $this->addLastOperation(Operations::last($this->instance()));
    }
    
    /**
     * Return last element from stream or default when stream is empty.
     *
     * @param callable|mixed|null $orElse
     */
    public function lastOrElse($orElse): LastOperation
    {
        return $this->addLastOperation(Operations::last($this->instance(), $orElse));
    }
    
    /**
     * It collects repeatable values in collections by their keys.
     */
    public function group(?bool $reindex = null): BaseStreamCollection
    {
        return $this->groupBy(Discriminators::byKey(), $reindex);
    }
    
    /**
     * It collects repeatable values in collections by given discriminator.
     *
     * @param DiscriminatorReady|callable|array<string|int>|string|int $discriminator
     */
    public function groupBy($discriminator, ?bool $reindex = null): BaseStreamCollection
    {
        $groupBy = Operations::groupBy($discriminator, $reindex);
        $this->runWith($groupBy);
        
        return $groupBy->result();
    }
    
    /**
     * @param ConsumerReady|callable|resource $consumers
     */
    public function forEach(...$consumers): void
    {
        $this->runWith(Operations::call(...$consumers));
    }
    
    private function runWith(Operation $operation): void
    {
        $this->addOperation($operation)->run();
    }
    
    /**
     * Feed stream recursively with its own output.
     *
     * @param bool $run when true then run immediately
     */
    public function loop(bool $run = false): Executable
    {
        $instance = $this->instance();
        
        $instance->registerFeedStream($instance);
        $instance->chainOperation(Operations::feed($instance));
        
        if ($run) {
            $instance->run();
        }
        
        return $instance;
    }
    
    /**
     * Run stream pipeline.
     * Stream can be executed only once!
     */
    public function run(bool $onlyIfNotRunYet = false): void
    {
        if ($this->isPrototype && !empty($this->joinProducers)) {
            $this->initialize();
            
            foreach ($this->joinProducers as $producers) {
                $this->source->addProducers($producers);
            }
            
            $this->joinProducers = [];
        }
        
        if ($onlyIfNotRunYet && !$this->isNotStartedYet()) {
            return;
        }
        
        while (!empty($this->parents)) {
            $parent = \array_shift($this->parents);
            $parent->run(true);
        }
        
        if ($this->isExecuted && isset($parent)) {
            return;
        }
        
        $this->execute();
    }
    
    /**
     *  Experimental. Causes stream to consume data provided by the passed producer. Can be called many times.
     *  However, calling wrap() after calling consume() will result in an exception.
     *
     * @param ProducerReady|\Traversable<mixed>|resource|callable|iterable<mixed>|string $producer
     */
    public function consume($producer): void
    {
        if (!$this->isInitialized) {
            $this->prepareToRun();
            $this->initialize();
            
            $this->isConsumer = true;
        }
        
        if (!$this->signal->isWorking) {
            return;
        }
        
        $this->refreshResult();
        $item = $this->signal->item;
        
        foreach (Producers::getAdapter($producer) as $item->key => $item->value) {
            try {
                goto PUSH_ITEM; //THIS IS MARVELOUS
                
                ITERATION_LOOP:
                if ($this->signal->isWorking) {
                    
                    TRY_AGAIN:
                    if ($this->source->hasNextItem()) {
                        PUSH_ITEM:
                        $this->pipe->head->handle($this->signal);
                    } elseif (empty($this->pipe->stack)) {
                        $this->signal->streamIsEmpty();
                    } else {
                        $this->isFirstProducer = $this->source->restoreFromStack();
                        $this->signal->resume();
                    }
                    
                    if ($this->isFirstProducer) {
                        continue;
                    }
                    
                    goto ITERATION_LOOP;
                }
                
                if ($this->shouldContinueAfterStreamingFinished()) {
                    goto TRY_AGAIN;
                }
                
            } catch (Interruption|AssertionFailed $e) {
                throw $e;
            } catch (\Throwable $e) {
                if ($this->shouldContinueAfterError($e)) {
                    if ($this->isFirstProducer) {
                        continue;
                    }
                    
                    goto ITERATION_LOOP;
                }
            }
            
            break;
        }
    }
    
    private function refreshResult(): void
    {
        $lastOperation = $this->getLastOperation();
        
        if ($lastOperation instanceof FinalOperation) {
            $lastOperation->refreshResult();
        }
    }
    
    protected function resume(): void
    {
        if ($this->isInitialized && !$this->isResuming) {
            $this->isResuming = true;
            try {
                $this->pipe->resume();
                $this->signal->resume();
            } finally {
                $this->isResuming = false;
            }
        }
    }
    
    private function execute(): void
    {
        $this->isStarted = true;
        
        $this->prepareToRun();
        $this->iterateStream();
        $this->finish();
    }
    
    private function isNotStartedYet(): bool
    {
        $notStarted = !$this->isStarted && !$this->isExecuted;
        
        foreach ($this->parents as $parent) {
            $notStarted = $notStarted && $parent->isNotStartedYet();
        }
        
        return $notStarted;
    }
    
    private function iterateStream(): void
    {
        if ($this->canBuildPowerStream()) {
            foreach ($this->pipe->buildStream($this->producer) as $_) {
                //noop - just iterate stream
            }
        } else {
            $this->initialize();
            $this->continueIteration();
        }
    }
    
    /**
     * @inheritdoc
     */
    public function getIterator(): \Iterator
    {
        if ($this->canBuildPowerStream()) {
            $this->prepareToRun();
            
            return (function (): \Generator {
                yield from $this->pipe->buildStream($this->producer);
                $this->finish();
            })();
        }
        
        $this->prepareForIterate();
        
        return (function (): \Generator {
            $item = $this->signal->item;
            
            try {
                $this->run();
                return;
                
                ITERATION_LOOP:
                if ($this->signal->isWorking) {
                    
                    TRY_AGAIN:
                    if ($this->source->hasNextItem()) {
                        $this->pipe->head->handle($this->signal);
                    } elseif (empty($this->pipe->stack)) {
                        $this->signal->streamIsEmpty();
                    } else {
                        $this->isFirstProducer = $this->source->restoreFromStack();
                        $this->signal->resume();
                    }
                    
                    goto ITERATION_LOOP;
                }
                
                if ($this->shouldContinueAfterStreamingFinished()) {
                    goto TRY_AGAIN;
                }
                
            } catch (Interruption $_) {
                yield $item->key => $item->value;
                goto ITERATION_LOOP;
            } catch (AssertionFailed $e) {
                throw $e;
            } catch (\Throwable $e) {
                if ($this->shouldContinueAfterError($e)) {
                    goto ITERATION_LOOP;
                }
            }

            $this->finish();
        })();
    }
    
    private function prepareForIterate(): void
    {
        $this->chainOperation(new Iterate());
        $this->initialize();
    }
    
    protected function canBuildPowerStream(): bool
    {
        return empty($this->onErrorHandlers) && !$this->isLoop && !$this->pipe->containsSwapOperation();
    }
    
    private function prepareToRun(): void
    {
        if ($this->isExecuted) {
            if ($this->isConsumer) {
                $this->isConsumer = false;
            } else {
                throw StreamExceptionFactory::cannotExecuteStreamMoreThanOnce();
            }
        }
        
        $this->pipe->prepare();
    }
    
    /**
     * Experimental. Do not use it.
     */
    public function destroy(): void
    {
        if ($this->isDestroying) {
            return;
        }
    
        $this->isDestroying = true;
        $this->isExecuted = true;
        
        $this->pipe->destroy();
        $this->producer->destroy();
        $this->parents = [];
        
        if ($this->isInitialized) {
            $this->sources->destroy();
            $this->source->destroy();
        }
        
        $this->resetState();
    }
    
    private function resetState(): void
    {
        $this->isLoop = false;
        $this->isFirstProducer = true;
        $this->isResuming = false;
        $this->streamingFinished = false;
        
        $this->pushToStreams = [];
        $this->onFinishHandlers = [];
        $this->onSuccessHandlers = [];
        $this->onErrorHandlers = [];
    }
    
    private function initialize(): void
    {
        if (!$this->isInitialized) {
            $this->signal = new Signal($this);
            $this->sources = new Sources();
            
            $this->setSource(new SourceNotReady(
                new SourceData($this, $this->signal, $this->pipe, $this->sources),
                $this->producer
            ));
            
            $this->isInitialized = true;
        }
    }
    
    protected function finish(): void
    {
        $this->isExecuted = true;
        
        while (!empty($this->pushToStreams)) {
            foreach ($this->pushToStreams as $key => $stream) {
                if ($this->isLoop || !$stream->continueIteration()) {
                    unset($this->pushToStreams[$key]);
                    
                    $stream->resume();
                    $stream->finish();
                }
            }
        }
        
        if (!$this->isInitialized || !$this->signal->isError) {
            foreach ($this->onSuccessHandlers as $handler) {
                $handler();
            }
        }
        
        foreach ($this->onFinishHandlers as $handler) {
            $handler();
        }
    }
    
    protected function continueIteration(bool $once = false): bool
    {
        try {
            ITERATION_LOOP:
            if ($this->signal->isWorking) {
                
                TRY_AGAIN:
                if ($this->source->hasNextItem()) {
                    $this->pipe->head->handle($this->signal);
                } elseif (empty($this->pipe->stack)) {
                    $this->signal->streamIsEmpty();
                } else {
                    $this->isFirstProducer = $this->source->restoreFromStack();
                    $this->signal->resume();
                }
                
                if ($once && $this->isFirstProducer) {
                    return true;
                }
                
                goto ITERATION_LOOP;
            }
            
            if ($this->shouldContinueAfterStreamingFinished()) {
                goto TRY_AGAIN;
            }
            
        } catch (Interruption|AssertionFailed $e) {
            throw $e;
        } catch (\Throwable $e) {
            if ($this->shouldContinueAfterError($e)) {
                goto ITERATION_LOOP;
            }
        }
        
        return false;
    }
    
    private function shouldContinueAfterStreamingFinished(): bool
    {
        $this->streamingFinished = true;
        
        if ($this->pipe->head->streamingFinished($this->signal)) {
            $this->streamingFinished = false;
            return true;
        }
        
        return false;
    }
    
    private function shouldContinueAfterError(\Throwable $e): bool
    {
        foreach ($this->onErrorHandlers as $handler) {
            $skip = $handler->handle($e, $this->signal->item->key, $this->signal->item->value);
            
            if ($skip === true && !$this->streamingFinished) {
                return true;
            }
            
            if ($skip === false) {
                $this->signal->abort();
                return false;
            }
        }
        
        throw $e;
    }
    
    protected function restartWith(Producer $producer, Operation $operation): void
    {
        $this->source->restartWith($producer, $operation);
    }
    
    protected function continueWith(Producer $producer, Operation $operation): void
    {
        $this->isFirstProducer = false;
        $this->source->continueWith($producer, $operation);
    }
    
    protected function forget(Operation $operation): void
    {
        $this->source->forget($operation);
    }
    
    protected function swapHead(Operation $operation): void
    {
        $this->source->swapHead($operation);
    }
    
    protected function restoreHead(): void
    {
        $this->source->restoreHead();
    }
    
    protected function setNextItem(Item $item): void
    {
        $this->source->setNextItem($item);
    }
    
    protected function limitReached(Operation $operation): void
    {
        $this->source->limitReached($operation);
    }
    
    private function addLastOperation(Operation $operation): LastOperation
    {
        $next = $this->instance()->chainOperation($operation);
        \assert($next instanceof LastOperation);
        
        return $next;
    }
    
    private function addOperation(Operation $operation): Stream
    {
        $instance = $this->instance();
        $instance->chainOperation($operation);
        
        return $instance;
    }
    
    private function instance(): Stream
    {
        return $this->isPrototype ? clone $this : $this;
    }
    
    private function registerFeedStream(StreamPipe $stream): void
    {
        $id = \spl_object_id($stream);
        
        if (!isset($this->pushToStreams[$id])) {
            $this->pushToStreams[$id] = $stream;
            
            if ($stream === $this) {
                $this->isLoop = true;
            }
            
            $stream->prepareSubstream($this->isLoop);
        }
    }
    
    private function chainOperation(Operation $next): Operation
    {
        $operation = $this->pipe->chainOperation($next);
        \assert($operation instanceof Operation);
        
        $operation->assignStream($this);
        
        return $operation;
    }
    
    protected function prepareSubstream(bool $isLoop): void
    {
        $this->initialize();
        $this->source->prepareSubstream($isLoop);
    }
    
    protected function process(Signal $signal): bool
    {
        $this->source->setNextItem($signal->item);
        
        return $this->isLoop || $this->continueIteration($this->isFirstProducer);
    }
    
    protected function getLastOperation(): ?LastOperation
    {
        $last = $this->pipe->last;
        
        return $last instanceof LastOperation ? $last : null;
    }
    
    protected function setSource(Source $state): void
    {
        $this->source = $state;
        $this->producer = $state->producer;
    }
    
    protected function assignParent(Stream $stream): void
    {
        if ($stream !== $this) {
            $this->parents[\spl_object_id($stream)] = $stream;
        }
    }
    
    protected function isPrototype(): bool
    {
        return $this->isPrototype;
    }
}