<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw;

use FiiSoft\Jackdaw\Collector\Collector;
use FiiSoft\Jackdaw\Comparator\{Comparable, ComparatorReady, Sorting\By, Sorting\Sorting};
use FiiSoft\Jackdaw\Condition\ConditionReady;
use FiiSoft\Jackdaw\Consumer\{ConsumerReady, Consumers};
use FiiSoft\Jackdaw\Discriminator\{DiscriminatorReady, Discriminators};
use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Exception\StreamExceptionFactory;
use FiiSoft\Jackdaw\Filter\{Filter, Filters};
use FiiSoft\Jackdaw\Handler\{ErrorHandler, OnError};
use FiiSoft\Jackdaw\Internal\{Check, Collection\BaseStreamCollection, Destroyable, Executable, ForkCollaborator, Helper,
    Iterator\BaseFastIterator, Iterator\BaseStreamIterator, Iterator\Interruption, Pipe, Signal, SignalHandler,
    State\Source, State\SourceNotReady, State\Stack, State\StreamSource, StreamPipe};
use FiiSoft\Jackdaw\Mapper\{Internal\ConditionalExtract, MapperReady, Mappers};
use FiiSoft\Jackdaw\Operation\{Internal\Shuffle, LastOperation, Operation};
use FiiSoft\Jackdaw\Operation\Collecting\{Categorize, Fork, Gather, Reverse, Segregate, Sort, SortLimited, Tail};
use FiiSoft\Jackdaw\Operation\Filtering\{EveryNth, Extrema, Filter as OperationFilter, FilterBy, FilterWhen,
    FilterWhile, Increasing, Maxima, OmitReps, Skip, SkipWhile, Unique, Uptrends};
use FiiSoft\Jackdaw\Operation\Mapping\{Accumulate, Aggregate, Chunk, ChunkBy, Classify, Flat, Flip, Map, MapFieldWhen,
    MapKey, MapKeyValue, MapWhen, MapWhile, Reindex, Scan, Tokenize, Tuple, UnpackTuple, Window, Zip};
use FiiSoft\Jackdaw\Operation\Sending\{CollectIn, CollectKeysIn, CountIn, Dispatch, Dispatcher\HandlerReady, Feed,
    FeedMany, Remember, SendTo, SendToMany, SendToMax, SendWhen, SendWhile, StoreIn, Unzip};
use FiiSoft\Jackdaw\Operation\Special\{Assert, Assert\AssertionFailed, Iterate, Limit, Until};
use FiiSoft\Jackdaw\Operation\Terminating\{Collect, CollectKeys, Count, FinalOperation, Find, First, Fold, GroupBy, Has,
    HasEvery, HasOnly, IsEmpty, Last, Reduce};
use FiiSoft\Jackdaw\Producer\{Internal\EmptyProducer, Internal\PushProducer, Producer, ProducerReady, Producers};
use FiiSoft\Jackdaw\Reducer\Reducer;
use FiiSoft\Jackdaw\Registry\RegWriter;

final class Stream extends StreamSource
    implements HandlerReady, SignalHandler, Executable, Destroyable, \IteratorAggregate
{
    private Producer $producer;
    private Source $source;
    private Signal $signal;
    private Stack $stack;
    private Pipe $pipe;
    
    private bool $isExecuted = false;
    private bool $isLoop = false;
    private bool $isFirstProducer = true;
    private bool $isDestroying = false;
    private bool $isInitialized = false;
    private bool $isStarted = false;
    private bool $canFinish = true;
    
    /** @var StreamPipe[] */
    private array $pushToStreams = [];
    
    /** @var callable[] */
    private array $onFinishHandlers = [];
    
    /** @var callable[] */
    private array $onSuccessHandlers = [];
    
    /** @var ErrorHandler[] */
    private array $onErrorHandlers = [];
    
    /**
     * @param ProducerReady|resource|callable|iterable|scalar ...$elements
     */
    public static function of(...$elements): Stream
    {
        return self::from(Producers::from($elements));
    }
    
    /**
     * @param ProducerReady|resource|callable|iterable $producer
     */
    public static function from($producer): Stream
    {
        return new self(Producers::getAdapter($producer));
    }
    
    public static function empty(): Stream
    {
        return new self(new EmptyProducer());
    }
    
    private function __construct(Producer $producer)
    {
        $this->producer = $producer;
        
        $this->pipe = new Pipe();
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
        $this->pipe->prepare();
        
        $this->isInitialized = false;
        $this->isExecuted = false;
        $this->isStarted = false;
        $this->isLoop = false;
        $this->isFirstProducer = true;
        $this->canFinish = true;
        
        $this->pushToStreams = [];
        $this->onFinishHandlers = [];
        $this->onSuccessHandlers = [];
        $this->onErrorHandlers = [];
        
        $this->producer = new PushProducer();
        
        $this->initialize();
    }
    
    public function limit(int $limit): Stream
    {
        $this->chainOperation(new Limit($limit));
        return $this;
    }
    
    public function skip(int $offset): Stream
    {
        $this->chainOperation(new Skip($offset));
        return $this;
    }
    
    /**
     * @param Filter|callable|mixed $filter
     */
    public function skipWhile($filter, ?int $mode = null): Stream
    {
        $this->chainOperation(new SkipWhile($filter, $mode));
        return $this;
    }
    
    /**
     * @param Filter|callable|mixed $filter
     */
    public function skipUntil($filter, ?int $mode = null): Stream
    {
        $this->chainOperation(new SkipWhile($filter, $mode, true));
        return $this;
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
    
    public function without(array $values, int $mode = Check::VALUE): Stream
    {
        if (\count($values) === 1) {
            $filter = Filters::same($values[\array_key_first($values)], $mode);
        } else {
            $filter = Filters::onlyIn($values, $mode);
        }
        
        return $this->omit($filter);
    }
    
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
     * @param array|string|int $fields
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
     * @param Filter|callable|mixed $filter
     * @throws AssertionFailed
     */
    public function assert($filter, ?int $mode = null): Stream
    {
        $this->chainOperation(new Assert($filter, $mode));
        return $this;
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
     * @param Filter|callable|mixed $filter
     */
    public function filterBy($field, $filter): Stream
    {
        $this->chainOperation(new FilterBy($field, $filter));
        return $this;
    }
    
    /**
     * @param Filter|callable|mixed $filter
     */
    public function filter($filter, ?int $mode = null): Stream
    {
        $this->chainOperation(new OperationFilter($filter, false, $mode));
        return $this;
    }
    
    /**
     * @param ConditionReady|callable $condition
     * @param Filter|callable|mixed $filter
     */
    public function filterWhen($condition, $filter, ?int $mode = null): Stream
    {
        $this->chainOperation(new FilterWhen($condition, $filter, false, $mode));
        return $this;
    }
    
    /**
     * @param ConditionReady|callable $condition
     * @param Filter|callable|mixed $filter
     */
    public function filterWhile($condition, $filter, ?int $mode = null): Stream
    {
        $this->chainOperation(new FilterWhile($condition, $filter, $mode));
        return $this;
    }
    
    /**
     * @param ConditionReady|callable $condition
     * @param Filter|callable|mixed $filter
     */
    public function filterUntil($condition, $filter, ?int $mode = null): Stream
    {
        $this->chainOperation(new FilterWhile($condition, $filter, $mode, true));
        return $this;
    }
    
    /**
     * @param string|int $field
     * @param Filter|callable|mixed $filter
     */
    public function omitBy($field, $filter): Stream
    {
        $this->chainOperation(new FilterBy($field, $filter, true));
        return $this;
    }
    
    /**
     * @param Filter|callable|mixed $filter
     */
    public function omit($filter, ?int $mode = null): Stream
    {
        $this->chainOperation(new OperationFilter($filter, true, $mode));
        return $this;
    }
    
    /**
     * @param ConditionReady|callable $condition
     * @param Filter|callable|mixed $filter
     */
    public function omitWhen($condition, $filter, ?int $mode = null): Stream
    {
        $this->chainOperation(new FilterWhen($condition, $filter, true, $mode));
        return $this;
    }
    
    /**
     * This operation skips all repeatable consecutive values in series, so each value is different than previous one.
     * Unlike Unique, values can repeat in whole stream, but not in succession.
     *
     * @param ComparatorReady|callable|null $comparison
     */
    public function omitReps($comparison = null): Stream
    {
        $this->chainOperation(new OmitReps($comparison));
        return $this;
    }
    
    /**
     * @param array|string|int|null $fields
     */
    public function castToInt($fields = null): Stream
    {
        return $this->map(Mappers::toInt($fields));
    }
    
    /**
     * @param array|string|int|null $fields
     */
    public function castToFloat($fields = null): Stream
    {
        return $this->map(Mappers::toFloat($fields));
    }
    
    /**
     * @param array|string|int|null $fields
     */
    public function castToString($fields = null): Stream
    {
        return $this->map(Mappers::toString($fields));
    }
    
    /**
     * @param array|string|int|null $fields
     */
    public function castToBool($fields = null): Stream
    {
        return $this->map(Mappers::toBool($fields));
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
     * It requires array (or \ArrayAccessAdapter) value to work with.
     *
     * @param array $keys map names of keys to rename (before => after)
     */
    public function remap(array $keys): Stream
    {
        return $this->map(Mappers::remap($keys));
    }
    
    /**
     * @param MapperReady|callable|iterable|mixed $mapper
     */
    public function map($mapper): Stream
    {
        $this->chainOperation(new Map($mapper));
        return $this;
    }
    
    /**
     * @param ConditionReady|callable $condition
     * @param MapperReady|callable|iterable|mixed $mapper
     * @param MapperReady|callable|iterable|mixed|null $elseMapper
     */
    public function mapWhen($condition, $mapper, $elseMapper = null): Stream
    {
        $this->chainOperation(new MapWhen($condition, $mapper, $elseMapper));
        return $this;
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
     * @param ConditionReady|callable $condition
     * @param MapperReady|callable|iterable|mixed $mapper
     * @param MapperReady|callable|iterable|mixed|null $elseMapper
     */
    public function mapFieldWhen($field, $condition, $mapper, $elseMapper = null): Stream
    {
        $this->chainOperation(new MapFieldWhen($field, $condition, $mapper, $elseMapper));
        return $this;
    }
    
    /**
     * @param ConditionReady|callable $condition
     * @param MapperReady|callable|iterable|mixed $mapper
     */
    public function mapWhile($condition, $mapper): Stream
    {
        $this->chainOperation(new MapWhile($condition, $mapper));
        return $this;
    }
    
    /**
     * @param ConditionReady|callable $condition
     * @param MapperReady|callable|iterable|mixed $mapper
     */
    public function mapUntil($condition, $mapper): Stream
    {
        $this->chainOperation(new MapWhile($condition, $mapper, true));
        return $this;
    }
    
    /**
     * It works very similarly to mapKey - the difference is that it uses Discriminator as mapper
     * and guarantees that key is string, int or bool.
     *
     * @param DiscriminatorReady|callable|array $discriminator
     */
    public function classify($discriminator): Stream
    {
        $this->chainOperation(new Classify($discriminator));
        return $this;
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
        $this->chainOperation(new MapKey($mapper));
        return $this;
    }
    
    /**
     * This is specialized map operation which maps both key and value at the same time.
     * Callable $factory can accept zero, one (value) or two (value, key) params and MUST return array
     * with exactly one element - new pair of [key => value] passed to next step in stream.
     *
     * Value in this pair can be instance of Mapper and it will be used to compute real value of next element.
     *
     * @param callable $keyValueMapper
     */
    public function mapKV(callable $keyValueMapper): Stream
    {
        $this->chainOperation(MapKeyValue::create($keyValueMapper));
        return $this;
    }
    
    /**
     * Each time the signal reaches this operation, the value of passed variable is increased by 1.
     *
     * @param int $counter REFERENCE
     */
    public function countIn(int &$counter): Stream
    {
        $this->chainOperation(new CountIn($counter));
        return $this;
    }
    
    /**
     * It works in a similar way to method collectIn() and allows to store values with keys
     * in given array $buffer instead of Collector.
     *
     * @param \ArrayAccess|array $buffer REFERENCE
     */
    public function storeIn(&$buffer, bool $reindex = false): Stream
    {
        $this->chainOperation(StoreIn::create($buffer, $reindex));
        return $this;
    }
    
    /**
     * @param Collector|\ArrayAccess|\SplHeap|\SplPriorityQueue $collector
     */
    public function collectIn($collector, ?bool $reindex = null): Stream
    {
        $this->chainOperation(CollectIn::create($collector, $reindex));
        return $this;
    }
    
    /**
     * @param Collector|\ArrayAccess|\SplHeap|\SplPriorityQueue $collector
     */
    public function collectKeysIn($collector): Stream
    {
        $this->chainOperation(new CollectKeysIn($collector));
        return $this;
    }
    
    /**
     * @param ConsumerReady|callable|resource $consumers resource must be writeable
     */
    public function call(...$consumers): Stream
    {
        if (\count($consumers) === 1) {
            $this->chainOperation(new SendTo(...$consumers));
        } else {
            $this->chainOperation(new SendToMany(...$consumers));
        }
        return $this;
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
        $this->chainOperation(new SendToMax($times, $consumer));
        return $this;
    }
    
    /**
     * @param ConditionReady|callable $condition
     * @param ConsumerReady|callable|resource $consumer
     * @param ConsumerReady|callable|resource|null $elseConsumer
     */
    public function callWhen($condition, $consumer, $elseConsumer = null): Stream
    {
        $this->chainOperation(new SendWhen($condition, $consumer, $elseConsumer));
        return $this;
    }
    
    /**
     * @param ConditionReady|callable $condition
     * @param ConsumerReady|callable|resource $consumer
     */
    public function callWhile($condition, $consumer): Stream
    {
        $this->chainOperation(new SendWhile($condition, $consumer));
        return $this;
    }
    
    /**
     * @param ConditionReady|callable $condition
     * @param ConsumerReady|callable|resource $consumer
     */
    public function callUntil($condition, $consumer): Stream
    {
        $this->chainOperation(new SendWhile($condition, $consumer, true));
        return $this;
    }
    
    /**
     * Sugar syntax to store current value or key in some external variable.
     *
     * @param mixed $variable REFERENCE
     */
    public function putIn(&$variable, int $mode = Check::VALUE): Stream
    {
        $mode = Check::getMode($mode);
        
        if ($mode === Check::VALUE) {
            return $this->call(Consumers::sendValueTo($variable));
        }
        
        if ($mode === Check::KEY) {
            return $this->call(Consumers::sendKeyTo($variable));
        }
        
        throw StreamExceptionFactory::invalidModeForPutInOperation();
    }
    
    /**
     * @param ProducerReady|resource|callable|iterable ...$producers
     */
    public function join(...$producers): Stream
    {
        $this->initialize();
        
        $this->source->addProducers($producers);
        
        return $this;
    }
    
    /**
     * @param ComparatorReady|callable|null $comparison
     */
    public function unique($comparison = null): Stream
    {
        $this->chainOperation(new Unique($comparison));
        return $this;
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
     * @param Comparable|callable|null $sorting
     */
    public function sort($sorting = null): Stream
    {
        $this->chainOperation(new Sort($sorting));
        return $this;
    }
    
    /**
     * Reversed (descending) sorting.
     *
     * @param Comparable|callable|null $sorting
     */
    public function rsort($sorting = null): Stream
    {
        $this->chainOperation(new Sort(Sorting::reverse($sorting)));
        return $this;
    }
    
    /**
     * Normal sorting with limited number of {$limit} first values passed further to stream.
     *
     * @param Comparable|callable|null $sorting
     */
    public function best(int $limit, $sorting = null): Stream
    {
        $this->chainOperation(SortLimited::create($limit, $sorting));
        return $this;
    }
    
    /**
     * Reversed sorting with limited number of {$limit} values passed further to stream.
     *
     * @param Comparable|callable|null $sorting
     */
    public function worst(int $limit, $sorting = null): Stream
    {
        $this->chainOperation(SortLimited::create($limit, Sorting::reverse($sorting)));
        return $this;
    }
    
    /**
     * Collect all incoming elements from stream and when there are no more elements,
     * reverse their order and start streaming again.
     */
    public function reverse(): Stream
    {
        $this->chainOperation(new Reverse());
        return $this;
    }
    
    /**
     * Collect all incoming elements from stream and when there are no more elements,
     * start streaming them again in randomized order.
     *
     * @param int|null $chunkSize when > 1 it collects and shuffles chunks of data
     */
    public function shuffle(?int $chunkSize = null): Stream
    {
        $this->chainOperation(Shuffle::create($chunkSize));
        return $this;
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
        $this->chainOperation(new Reindex($start, $step));
        return $this;
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
            return $this->mapKV(static fn($value, $key): array => [
                $keyExtractor->map($value, $key) => Mappers::remove($field)
            ]);
        }
        
        return $this->mapKey($keyExtractor);
    }
    
    /**
     * Flips values with keys
     */
    public function flip(): Stream
    {
        $this->chainOperation(new Flip());
        return $this;
    }
    
    /**
     * @param mixed $initial
     * @param Reducer|callable $reducer
     */
    public function scan($initial, $reducer): Stream
    {
        $this->chainOperation(new Scan($initial, $reducer));
        return $this;
    }
    
    public function chunk(int $size, bool $reindex = false): Stream
    {
        $this->chainOperation(Chunk::create($size, $reindex));
        return $this;
    }
    
    /**
     * @param DiscriminatorReady|callable|array|string|int $discriminator
     */
    public function chunkBy($discriminator, bool $reindex = false): Stream
    {
        $this->chainOperation(ChunkBy::create($discriminator, $reindex));
        return $this;
    }
    
    public function window(int $size, int $step = 1, bool $reindex = false): Stream
    {
        $this->chainOperation(new Window($size, $step, $reindex));
        return $this;
    }
    
    public function everyNth(int $num): Stream
    {
        $this->chainOperation(new EveryNth($num));
        return $this;
    }
    
    /**
     * @param Filter|callable|mixed $filter
     */
    public function accumulate($filter, bool $reindex = false, ?int $mode = null): Stream
    {
        $this->chainOperation(Accumulate::create($filter, $mode, $reindex));
        return $this;
    }
    
    /**
     * @param Filter|callable|mixed $filter
     */
    public function separateBy($filter, bool $reindex = false, ?int $mode = null): Stream
    {
        $this->chainOperation(Accumulate::create($filter, $mode, $reindex, true));
        return $this;
    }
    
    public function aggregate(array $keys): Stream
    {
        $this->chainOperation(Aggregate::create($keys));
        return $this;
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
     * @param array|string|int $fields
     * @param mixed|null $orElse
     */
    public function extract($fields, $orElse = null): Stream
    {
        return $this->map(Mappers::extract($fields, $orElse));
    }
    
    /**
     * @param Filter|callable|mixed $filter
     */
    public function extractWhen($filter, ?int $mode = null): Stream
    {
        return $this->map(new ConditionalExtract($filter, $mode));
    }
    
    /**
     * @param array|string|int $fields
     */
    public function remove(...$fields): Stream
    {
        if (\count($fields) === 1 && \is_array($fields[0] ?? null)) {
            $fields = $fields[0];
        }
        
        return $this->map(Mappers::remove($fields));
    }
    
    /**
     * @param Filter|callable|mixed $filter
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
        $this->chainOperation(new Tokenize($tokens));
        return $this;
    }
    
    public function flat(int $level = 0): Stream
    {
        $this->chainOperation(new Flat($level));
        return $this;
    }
    
    /**
     * @param MapperReady|callable|iterable|mixed $mapper
     */
    public function flatMap($mapper, int $level = 0): Stream
    {
        return $this->map($mapper)->flat($level);
    }
    
    /**
     * @param SignalHandler ...$streams
     */
    public function feed(SignalHandler ...$streams): Stream
    {
        if (empty($streams)) {
            throw InvalidParamException::byName('streams');
        }
        
        foreach ($streams as $stream) {
            if ($stream instanceof StreamPipe) {
                $id = \spl_object_id($stream);
                
                if (!isset($this->pushToStreams[$id])) {
                    $this->pushToStreams[$id] = $stream;
                    
                    if ($stream === $this) {
                        $this->isLoop = true;
                    }
                    
                    $stream->prepareSubstream($this->isLoop);
                }
            } else {
                throw StreamExceptionFactory::feedOperationCanHandleStreamPipeOnly();
            }
        }
        
        if (\count($streams) === 1) {
            $this->chainOperation(new Feed($streams[0]));
        } else {
            $this->chainOperation(new FeedMany(...$streams));
        }
        
        return $this;
    }
    
    /**
     * @param DiscriminatorReady|callable|array $discriminator
     * @param HandlerReady[] $handlers
     */
    public function dispatch($discriminator, array $handlers): Stream
    {
        foreach ($handlers as $handler) {
            if ($handler === $this) {
                throw StreamExceptionFactory::dispatchOperationCannotHandleLoops();
            }
        }
        
        $this->chainOperation(new Dispatch($discriminator, $handlers));
        return $this;
    }
    
    /**
     * It works like conditional limit().
     *
     * @param Filter|callable|mixed $filter
     */
    public function while($filter, ?int $mode = null): Stream
    {
        $this->chainOperation(new Until($filter, $mode, true));
        return $this;
    }
    
    /**
     * It works like conditional limit().
     *
     * @param Filter|callable|mixed $filter
     */
    public function until($filter, ?int $mode = null): Stream
    {
        $this->chainOperation(new Until($filter, $mode));
        return $this;
    }
    
    /**
     * @param int $numOfItems number of Nth last elements
     */
    public function tail(int $numOfItems): Stream
    {
        $this->chainOperation(new Tail($numOfItems));
        return $this;
    }
    
    /**
     * It works similar to chunk, but it gathers all elements until stream is empty,
     * and then passes whole array as argument for next step.
     *
     * @param bool $reindex
     */
    public function gather(bool $reindex = false): Stream
    {
        $this->chainOperation(Gather::create($reindex));
        return $this;
    }
    
    /**
     * It collects elements in array as long as they meet given condition.
     * With first element which does not meet condition, gathering values is aborted
     * and array of collected elements is passed to next step. No other items in the stream will be read.
     *
     * @param Filter|callable|mixed $filter
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
     * @param Filter|callable|mixed $filter
     */
    public function gatherUntil($filter, bool $reindex = false, ?int $mode = null): Stream
    {
        return $this->until($filter, $mode)->gather($reindex);
    }
    
    /**
     * @param int|null $buckets null means collect all elements
     * @param Comparable|callable|null $comparison
     */
    public function segregate(?int $buckets = null, bool $reindex = false, $comparison = null): Stream
    {
        $this->chainOperation(new Segregate($buckets, $reindex, $comparison));
        return $this;
    }
    
    /**
     * @param DiscriminatorReady|callable|array|string|int $discriminator
     */
    public function categorize($discriminator, ?bool $reindex = null): Stream
    {
        $this->chainOperation(Categorize::create($discriminator, $reindex));
        return $this;
    }
    
    /**
     * @param string|int $field
     */
    public function categorizeBy($field, ?bool $reindex = null): Stream
    {
        return $this->categorize(Discriminators::byField($field), $reindex);
    }
    
    /**
     * Replace value of current element with its [key, value].
     * When param $assoc is true then it creates pair ['key' => key, 'value' => value].
     * In both cases, real key of element is reindexed starting from 0 (like in reindex() operation).
     */
    public function makeTuple(bool $assoc = false): Stream
    {
        $this->chainOperation(Tuple::create($assoc));
        return $this;
    }
    
    /**
     * This operation works in the opposite way to makeTuple() - it maps tuple from the value
     * ([key, value] or ['key' => key, 'value' => value]) to key and value of current element.
     */
    public function unpackTuple(bool $assoc = false): Stream
    {
        $this->chainOperation(UnpackTuple::create($assoc));
        return $this;
    }
    
    /**
     * Creates a numeric array where the first item comes from this stream, and the next items come from all passed
     * providers of subsequent values. In the event that any supplier runs out, null is inserted in its place.
     *
     * @param array<ProducerReady|resource|callable|iterable|scalar> $sources
     */
    public function zip(...$sources): Stream
    {
        $this->chainOperation(Zip::create($sources));
        return $this;
    }
    
    /**
     * @param HandlerReady ...$consumers
     */
    public function unzip(...$consumers): Stream
    {
        $this->chainOperation(new Unzip($consumers));
        return $this;
    }
    
    /**
     * @param DiscriminatorReady|callable|array $discriminator
     */
    public function fork($discriminator, LastOperation $prototype): Stream
    {
        if ($prototype instanceof ForkCollaborator) {
            $this->chainOperation(new Fork($discriminator, $prototype));
            return $this;
        }
        
        throw StreamExceptionFactory::forkOperationRequiresForkCollaborator();
    }
    
    /**
     * @param string|int $field
     */
    public function forkBy($field, LastOperation $prototype): Stream
    {
        return $this->fork(Discriminators::byField($field), $prototype);
    }
    
    /**
     * Remember key and/or value of current element in passed registry.
     */
    public function remember(RegWriter $registry): Stream
    {
        $this->chainOperation(new Remember($registry));
        return $this;
    }
    
    /**
     * @param Comparable|callable|null $comparison
     */
    public function accumulateUptrends(bool $reindex = false, $comparison = null): Stream
    {
        $this->chainOperation(Uptrends::create($reindex, false, $comparison));
        return $this;
    }
    
    /**
     * @param Comparable|callable|null $comparison
     */
    public function accumulateDowntrends(bool $reindex = false, $comparison = null): Stream
    {
        $this->chainOperation(Uptrends::create($reindex, true, $comparison));
        return $this;
    }
    
    /**
     * @param bool $allowLimits when true then allow for limit values (first and last element in the stream)
     * @param Comparable|callable|null $comparison
     */
    public function onlyMaxima(bool $allowLimits = true, $comparison = null): Stream
    {
        $this->chainOperation(new Maxima($allowLimits, false, $comparison));
        return $this;
    }
    
    /**
     * @param bool $allowLimits when true then allow for limit values (first and last element in the stream)
     * @param Comparable|callable|null $comparison
     */
    public function onlyMinima(bool $allowLimits = true, $comparison = null): Stream
    {
        $this->chainOperation(new Maxima($allowLimits, true, $comparison));
        return $this;
    }
    
    /**
     * @param bool $allowLimits when true then allow for limit values (first and last element in the stream)
     * @param Comparable|callable|null $comparison
     */
    public function onlyExtrema(bool $allowLimits = true, $comparison = null): Stream
    {
        $this->chainOperation(new Extrema($allowLimits, $comparison));
        return $this;
    }
    
    /**
     * @param Comparable|callable|null $comparison
     */
    public function increasingTrend($comparison = null): Stream
    {
        $this->chainOperation(new Increasing(false, $comparison));
        return $this;
    }
    
    /**
     * @param Comparable|callable|null $comparison
     */
    public function decreasingTrend($comparison = null): Stream
    {
        $this->chainOperation(new Increasing(true, $comparison));
        return $this;
    }
    
    /**
     * Create new stream from the current one and set provided Producer as source of data for it.
     *
     * @param ProducerReady|resource|callable|iterable $producer
     */
    public function wrap($producer): Stream
    {
        $copy = clone $this;
        $copy->producer = Producers::getAdapter($producer);
        
        return $copy;
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
        
        if ($handler instanceof ErrorHandler) {
            if ($replace) {
                $this->onErrorHandlers = [$handler];
            } else {
                $this->onErrorHandlers[] = $handler;
            }
        } else {
            throw InvalidParamException::describe('handler', $handler);
        }
        
        return $this;
    }
    
    /**
     * Register handlers which will be called at the end and only when no errors occurred.
     *
     * @param callable $handler
     * @param bool $replace when true then replace all existing handlers, when false then add handler to stack
     */
    public function onSuccess(callable $handler, bool $replace = false): Stream
    {
        if ($replace) {
            $this->onSuccessHandlers = [$handler];
        } else {
            $this->onSuccessHandlers[] = $handler;
        }
        
        return $this;
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
        if ($replace) {
            $this->onFinishHandlers = [$handler];
        } else {
            $this->onFinishHandlers[] = $handler;
        }
        
        return $this;
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
     */
    public function toArrayAssoc(): array
    {
        return $this->toArray(true);
    }
    
    public function toArray(bool $preserveKeys = false): array
    {
        $buffer = [];
        $this->runWith(StoreIn::create($buffer, !$preserveKeys));
        
        return $buffer;
    }
    
    /**
     * Collect all elements from stream.
     */
    public function collect(bool $reindex = false): LastOperation
    {
        return $this->runLast(Collect::create($this, $reindex));
    }
    
    /**
     * Collect all keys from stream.
     */
    public function collectKeys(): LastOperation
    {
        return $this->runLast(new CollectKeys($this));
    }
    
    /**
     * It collects data as long as the condition is true and then terminates processing when it is not.
     *
     * @param Filter|callable|mixed $filter
     */
    public function collectWhile($filter, ?int $mode = null): LastOperation
    {
        return $this->while($filter, $mode)->collect();
    }
    
    /**
     * @param Filter|callable|mixed $filter
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
        return $this->runLast(new Count($this));
    }
    
    /**
     * @param Reducer|callable|array $reducer
     * @param callable|mixed|null $orElse (default null)
     */
    public function reduce($reducer, $orElse = null): LastOperation
    {
        return $this->runLast(new Reduce($this, $reducer, $orElse));
    }
    
    /**
     * @param mixed $initial
     * @param Reducer|callable $reducer Callable accepts two arguments: accumulator and current value
     */
    public function fold($initial, $reducer): LastOperation
    {
        return $this->runLast(new Fold($this, $initial, $reducer));
    }
    
    /**
     * Tell if stream is not empty.
     */
    public function isNotEmpty(): LastOperation
    {
        return $this->runLast(new IsEmpty($this, false));
    }
    
    /**
     * Tell if stream is empty.
     */
    public function isEmpty(): LastOperation
    {
        return $this->runLast(new IsEmpty($this, true));
    }
    
    /**
     * Tell if element occurs in stream.
     *
     * @param Filter|callable|mixed $value
     */
    public function has($value, int $mode = Check::VALUE): LastOperation
    {
        return $this->runLast(new Has($this, $value, $mode));
    }
    
    public function hasAny(array $values, int $mode = Check::VALUE): LastOperation
    {
        return $this->has(Filters::onlyIn($values, $mode));
    }
    
    public function hasEvery(array $values, int $mode = Check::VALUE): LastOperation
    {
        return $this->runLast(HasEvery::create($this, $values, $mode));
    }
    
    public function hasOnly(array $values, int $mode = Check::VALUE): LastOperation
    {
        return $this->runLast(HasOnly::create($this, $values, $mode));
    }
    
    /**
     * Return first element in stream which satisfies given predicate or null when element was not found.
     *
     * @param Filter|callable|mixed $predicate
     */
    public function find($predicate, int $mode = Check::VALUE): LastOperation
    {
        return $this->runLast(new Find($this, $predicate, $mode));
    }
    
    /**
     * @param Filter|callable|mixed $predicate
     */
    public function findMax(int $limit, $predicate, int $mode = Check::VALUE): LastOperation
    {
        return $this->filter($predicate, $mode)->limit($limit)->collect();
    }
    
    /**
     * Return first available element from stream or null when stream is empty.
     */
    public function first(): LastOperation
    {
        return $this->runLast(new First($this));
    }
    
    /**
     * Return first available element from stream or default when stream is empty.
     *
     * @param callable|mixed|null $orElse
     */
    public function firstOrElse($orElse): LastOperation
    {
        return $this->runLast(new First($this, $orElse));
    }
    
    /**
     * Return last element from stream or null when stream is empty.
     */
    public function last(): LastOperation
    {
        return $this->runLast(new Last($this));
    }
    
    /**
     * Return last element from stream or default when stream is empty.
     *
     * @param callable|mixed|null $orElse
     */
    public function lastOrElse($orElse): LastOperation
    {
        return $this->runLast(new Last($this, $orElse));
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
     * @param DiscriminatorReady|callable|array|string|int $discriminator
     */
    public function groupBy($discriminator, ?bool $reindex = null): BaseStreamCollection
    {
        $groupBy = GroupBy::create($discriminator, $reindex);
        $this->runWith($groupBy);
        
        return $groupBy->result();
    }
    
    /**
     * @param ConsumerReady|callable|resource $consumer
     */
    public function forEach(...$consumer): void
    {
        $this->runWith(new SendTo(...$consumer));
    }
    
    private function runWith(Operation $operation): void
    {
        $this->chainOperation($operation);
        $this->run();
    }
    
    private function runLast(FinalOperation $operation): LastOperation
    {
        \assert($operation instanceof Operation);
        $next = $this->chainOperation($operation);
        
        \assert($next instanceof LastOperation);
        return $next;
    }
    
    /**
     * Feed stream recursively with its own output.
     *
     * @param bool $run when true then run immediately
     */
    public function loop(bool $run = false): Executable
    {
        $this->feed($this);
        
        if ($run) {
            $this->run();
        }
        
        return $this;
    }
    
    /**
     * Run stream pipeline.
     * Stream can be executed only once!
     */
    public function run(): void
    {
        $this->canFinish = true;
        
        $this->execute();
    }
    
    protected function execute(): void
    {
        $this->isStarted = true;
        
        $this->prepareToRun();
        $this->iterateStream();
        
        if ($this->canFinish) {
            $this->finish();
        }
    }
    
    protected function isNotStartedYet(): bool
    {
        return !$this->isStarted;
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
            
            return BaseFastIterator::create($this, $this->pipe->buildStream($this->producer));
        }
        
        $this->chainOperation(new Iterate());
        $this->initialize();
        
        return BaseStreamIterator::create($this, $this->signal->item);
    }
    
    private function canBuildPowerStream(): bool
    {
        return empty($this->onErrorHandlers) && !$this->isLoop;
    }
    
    private function prepareToRun(): void
    {
        if ($this->isExecuted) {
            throw StreamExceptionFactory::cannotExecuteStreamMoreThanOnce();
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
        $this->isLoop = false;
        $this->isFirstProducer = true;
        $this->canFinish = true;
        
        $this->pushToStreams = [];
        $this->onFinishHandlers = [];
        $this->onSuccessHandlers = [];
        $this->onErrorHandlers = [];
        
        $this->pipe->destroy();
        $this->producer->destroy();
        
        if ($this->isInitialized) {
            $this->stack->destroy();
            $this->source->destroy();
        }
    }
    
    private function initialize(): void
    {
        if (!$this->isInitialized) {
            $this->signal = new Signal($this);
            $this->stack = new Stack();
            
            $this->setSource(new SourceNotReady(
                $this->isLoop, $this, $this->producer, $this->signal, $this->pipe, $this->stack
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
        $streamingFinished = false;
        
        try {
            ITERATION_LOOP:
            if ($this->signal->isWorking) {
                
                PROCESS_NEXT_ITEM:
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
            
            $streamingFinished = true;
            if ($this->pipe->head->streamingFinished($this->signal)) {
                $streamingFinished = false;
                goto PROCESS_NEXT_ITEM;
            }
            
        } catch (Interruption|AssertionFailed $e) {
            throw $e;
        } catch (\Throwable $e) {
            foreach ($this->onErrorHandlers as $handler) {
                $skip = $handler->handle($e, $this->signal->item->key, $this->signal->item->value);
                if ($skip === true && !$streamingFinished) {
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
    
    protected function limitReached(Operation $operation): void
    {
        $this->source->limitReached($operation);
    }
    
    private function chainOperation(Operation $next): Operation
    {
        $operation = $this->pipe->chainOperation($next, $this);
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
    
    protected function getFinalOperation(): ?FinalOperation
    {
        $last = $this->pipe->last;
        
        return $last instanceof FinalOperation ? $last : null;
    }
    
    protected function setSource(Source $state): void
    {
        $this->source = $state;
        $this->producer = $state->producer;
    }
}