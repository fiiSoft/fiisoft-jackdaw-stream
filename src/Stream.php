<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw;

use FiiSoft\Jackdaw\Collector\Collector;
use FiiSoft\Jackdaw\Comparator\{Comparable, Comparison\Comparison, Sorting\By, Sorting\Sorting};
use FiiSoft\Jackdaw\Condition\Condition;
use FiiSoft\Jackdaw\Consumer\{Consumer, Consumers};
use FiiSoft\Jackdaw\Discriminator\{Discriminator, Discriminators};
use FiiSoft\Jackdaw\Filter\{Filter, Filters};
use FiiSoft\Jackdaw\Handler\{ErrorHandler, OnError};
use FiiSoft\Jackdaw\Internal\{Check, Collaborator, Collection\BaseStreamCollection, Destroyable, Executable,
    ForkCollaborator, Interruption, Iterator\StreamIterator, Iterator\StreamIterator81, Pipe, ResultApi, ResultCaster,
    Signal, SignalHandler, StreamPipe, State\Source, State\SourceNotReady, State\Stack};
use FiiSoft\Jackdaw\Mapper\{Internal\ConditionalExtract, Mapper, Mappers};
use FiiSoft\Jackdaw\Operation\{Accumulate, Aggregate, Assert, Categorize, Chunk, ChunkBy, Classify, CollectIn,
    CollectKeysIn, CountIn, Dispatch, Extrema, Filter as OperationFilter, FilterWhen, Flat, Flip, Gather, Increasing,
    Internal\AssertionFailed, Internal\Feed, Internal\FeedMany, Internal\FinalOperation, Internal\Fork,
    Internal\Iterate, Internal\LastOperation, Limit, Map, MapFieldWhen, MapKey, MapKeyValue, MapWhen, Maxima, OmitReps,
    Operation, Reindex, Remember, Reverse, Scan, Segregate, SendTo, SendToMax, SendWhen, Shuffle, Skip, SkipWhile, Sort,
    SortLimited, StoreIn, Tail, Terminating\Collect, Terminating\CollectKeys, Terminating\Count, Terminating\Find,
    Terminating\First, Terminating\Fold, Terminating\GroupBy, Terminating\Has, Terminating\HasEvery,
    Terminating\HasOnly, Terminating\IsEmpty, Terminating\Last, Terminating\Reduce, Terminating\Until, Tokenize, Tuple,
    Unique, UnpackTuple, Unzip, Uptrends, Zip};
use FiiSoft\Jackdaw\Predicate\{Predicate, Predicates};
use FiiSoft\Jackdaw\Producer\{Internal\PushProducer, Producer, Producers};
use FiiSoft\Jackdaw\Reducer\Reducer;
use FiiSoft\Jackdaw\Registry\RegWriter;

final class Stream extends Collaborator implements SignalHandler, Executable, Destroyable, \IteratorAggregate
{
    private Source $source;
    private Signal $signal;
    private Stack $stack;
    private Pipe $pipe;
    
    private bool $started = false;
    private bool $executed = false;
    private bool $isLoop = false;
    private bool $isFirstProducer = true;
    private bool $isDestroying = false;
    
    /** @var StreamPipe[] */
    private array $pushToStreams = [];
    
    /** @var callable[] */
    private array $onFinishHandlers = [];
    
    /** @var callable[] */
    private array $onSuccessHandlers = [];
    
    /** @var ErrorHandler[] */
    private array $onErrorHandlers = [];
    
    /**
     * @param Stream|Producer|ResultCaster|\Traversable|\PDOStatement|callable|resource|array|scalar ...$elements
     */
    public static function of(...$elements): Stream
    {
        return self::from(Producers::from($elements));
    }
    
    /**
     * @param Stream|Producer|ResultCaster|\Traversable|\PDOStatement|callable|resource|array $producer
     */
    public static function from($producer): Stream
    {
        return new self(Producers::getAdapter($producer));
    }
    
    public static function empty(): Stream
    {
        return self::from([]);
    }
    
    private function __construct(Producer $producer)
    {
        $this->signal = new Signal($this);
        $this->pipe = new Pipe();
        $this->stack = new Stack();
        
        $this->initializeSourceState($producer);
    }
    
    private function initializeSourceState(Producer $producer): void
    {
        $this->setSource(new SourceNotReady(
            $this->isLoop, $this, $producer, $this->signal, $this->pipe, $this->stack
        ));
    }
    
    protected function __clone()
    {
        $this->pipe = clone $this->pipe;
        $this->pipe->head->assignStream($this);
        $this->pipe->prepare();
        
        $this->signal = new Signal($this);
        $this->stack = new Stack();

        $this->started = false;
        $this->executed = false;
        $this->isLoop = false;
        $this->isFirstProducer = true;
        
        $this->pushToStreams = [];
        $this->onFinishHandlers = [];
        $this->onSuccessHandlers = [];
        $this->onErrorHandlers = [];
        
        $this->initializeSourceState(new PushProducer($this->isLoop));
    }
    
    protected function cloneStream(): Stream
    {
        return clone $this;
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
     * @param Filter|Predicate|callable|mixed $condition
     */
    public function skipWhile($condition, int $mode = Check::VALUE): Stream
    {
        $this->chainOperation(new SkipWhile($condition, $mode));
        return $this;
    }
    
    /**
     * @param Filter|Predicate|callable|mixed $condition
     */
    public function skipUntil($condition, int $mode = Check::VALUE): Stream
    {
        $this->chainOperation(new SkipWhile($condition, $mode, true));
        return $this;
    }
    
    /**
     * Filters out null values
     */
    public function notNull(): Stream
    {
        return $this->filter(Filters::notNull());
    }
    
    /**
     * Filters out empty values
     */
    public function notEmpty(): Stream
    {
        return $this->filter(Filters::notEmpty());
    }
    
    public function without(array $values, int $mode = Check::VALUE): Stream
    {
        if (\count($values) === 1) {
            $filter = Filters::same($values[\array_key_first($values)]);
        } else {
            $filter = Filters::onlyIn($values);
        }
        
        return $this->omit($filter, $mode);
    }
    
    public function only(array $values, int $mode = Check::VALUE): Stream
    {
        if (\count($values) === 1) {
            $filter = Filters::same($values[\array_key_first($values)]);
        } else {
            $filter = Filters::onlyIn($values);
        }
        
        return $this->filter($filter, $mode);
    }
    
    /**
     * @param array|string|int $keys list of keys or single key
     */
    public function onlyWith($keys, bool $allowNulls = false): Stream
    {
        return $this->filter(Filters::onlyWith($keys, $allowNulls));
    }
    
    /**
     * @param float|int $value
     */
    public function greaterThan($value): Stream
    {
        return $this->filter(Filters::greaterThan($value));
    }
    
    /**
     * @param float|int $value
     */
    public function greaterOrEqual($value): Stream
    {
        return $this->filter(Filters::greaterOrEqual($value));
    }
    
    /**
     * @param float|int $value
     */
    public function lessThan($value): Stream
    {
        return $this->omit(Filters::greaterOrEqual($value));
    }
    
    /**
     * @param float|int $value
     */
    public function lessOrEqual($value): Stream
    {
        return $this->omit(Filters::greaterThan($value));
    }
    
    /**
     * Filters out non-numeric values
     */
    public function onlyNumeric(int $mode = Check::VALUE): Stream
    {
        return $this->filter(Filters::isNumeric(), $mode);
    }
    
    /**
     * Filters out non-integer values
     */
    public function onlyIntegers(int $mode = Check::VALUE): Stream
    {
        return $this->filter(Filters::isInt(), $mode);
    }
    
    /**
     * Filters out non-string values
     */
    public function onlyStrings(int $mode = Check::VALUE): Stream
    {
        return $this->filter(Filters::isString(), $mode);
    }
    
    /**
     * Assert that element in stream satisfies given requirements.
     * If not, it throws non-catchable exception.
     *
     * @param Filter|Predicate|callable|mixed $filter
     * @throws AssertionFailed
     */
    public function assert($filter, int $mode = Check::VALUE): Stream
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
     * @param Filter|Predicate|callable|mixed $filter
     */
    public function filterBy($field, $filter): Stream
    {
        return $this->filter(Filters::filterBy($field, $filter));
    }
    
    /**
     * @param Filter|Predicate|callable|mixed $filter
     */
    public function filter($filter, int $mode = Check::VALUE): Stream
    {
        $this->chainOperation(new OperationFilter($filter, false, $mode));
        return $this;
    }
    
    /**
     * @param Condition|Predicate|Filter|callable $condition
     * @param Filter|Predicate|callable|mixed $filter
     */
    public function filterWhen($condition, $filter, int $mode = Check::VALUE): Stream
    {
        $this->chainOperation(new FilterWhen($condition, $filter, false, $mode));
        return $this;
    }
    
    /**
     * @param string|int $field
     * @param Filter|Predicate|callable|mixed $filter
     */
    public function omitBy($field, $filter): Stream
    {
        return $this->omit(Filters::filterBy($field, $filter));
    }
    /**
     * @param Filter|Predicate|callable|mixed $filter
     */
    public function omit($filter, int $mode = Check::VALUE): Stream
    {
        $this->chainOperation(new OperationFilter($filter, true, $mode));
        return $this;
    }
    
    /**
     * @param Condition|Predicate|Filter|callable $condition
     * @param Filter|Predicate|callable|mixed $filter
     */
    public function omitWhen($condition, $filter, int $mode = Check::VALUE): Stream
    {
        $this->chainOperation(new FilterWhen($condition, $filter, true, $mode));
        return $this;
    }
    
    /**
     * This operation skips all repeatable consecutive values in series, so each value is different than previous one.
     * Unlike Unique, values can repeat in whole stream, but not one after another.
     *
     * @param Comparison|Comparable|callable|null $comparison
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
     * @param Mapper|Reducer|Predicate|Filter|Discriminator|callable|array|mixed $mapper
     */
    public function map($mapper): Stream
    {
        $this->chainOperation(new Map($mapper));
        return $this;
    }
    
    /**
     * @param Condition|Predicate|Filter|callable $condition
     * @param Mapper|Reducer|Predicate|Filter|Discriminator|callable|array|mixed $mapper
     * @param Mapper|Reducer|Predicate|Filter|Discriminator|callable|array|mixed $elseMapper
     */
    public function mapWhen($condition, $mapper, $elseMapper = null): Stream
    {
        $this->chainOperation(new MapWhen($condition, $mapper, $elseMapper));
        return $this;
    }
    
    /**
     * @param string|int $field
     * @param Mapper|Reducer|Predicate|Filter|Discriminator|callable|array|mixed $mapper
     */
    public function mapField($field, $mapper): Stream
    {
        return $this->map(Mappers::mapField($field, $mapper));
    }
    
    /**
     * @param string|int $field
     * @param Condition|Predicate|Filter|callable $condition
     * @param Mapper|Reducer|Predicate|Filter|Discriminator|callable|array|mixed $mapper
     * @param Mapper|Reducer|Predicate|Filter|Discriminator|callable|array|mixed $elseMapper
     */
    public function mapFieldWhen($field, $condition, $mapper, $elseMapper = null): Stream
    {
        $this->chainOperation(new MapFieldWhen($field, $condition, $mapper, $elseMapper));
        return $this;
    }
    
    /**
     * It works very similarly to mapKey - the difference is that it uses Discriminator as mapper
     * and guarantees that key is string, int or bool.
     *
     * @param Discriminator|Condition|Predicate|Filter|Mapper|callable|array $discriminator
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
     * @param Mapper|Reducer|Predicate|Filter|Discriminator|callable|array|mixed $mapper
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
        $this->chainOperation(new MapKeyValue($keyValueMapper));
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
        $this->chainOperation(new StoreIn($buffer, $reindex));
        return $this;
    }
    
    /**
     * @param Collector|\ArrayAccess|\SplHeap|\SplPriorityQueue $collector
     */
    public function collectIn($collector, ?bool $reindex = null): Stream
    {
        $this->chainOperation(new CollectIn($collector, $reindex));
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
     * @param Consumer|Reducer|callable|resource $consumers resource must be writeable
     */
    public function call(...$consumers): Stream
    {
        $this->chainOperation(new SendTo(...$consumers));
        return $this;
    }
    
    /**
     * @param Consumer|callable|resource $consumer
     */
    public function callOnce($consumer): Stream
    {
        return $this->callMax(1, $consumer);
    }
    
    /**
     * @param Consumer|Reducer|callable|resource $consumer
     */
    public function callMax(int $times, $consumer): Stream
    {
        $this->chainOperation(new SendToMax($times, $consumer));
        return $this;
    }
    
    /**
     * @param Condition|Predicate|Filter|callable $condition
     * @param Consumer|Reducer|callable|resource $consumer
     * @param Consumer|Reducer|callable|resource|null $elseConsumer
     */
    public function callWhen($condition, $consumer, $elseConsumer = null): Stream
    {
        $this->chainOperation(new SendWhen($condition, $consumer, $elseConsumer));
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
        
        throw new \InvalidArgumentException('Only simple VALUE or KEY mode is supported');
    }
    
    /**
     * @param Stream|Producer|ResultCaster|\Iterator|\PDOStatement|callable|resource|array ...$producers
     */
    public function join(...$producers): Stream
    {
        $this->source->addProducers($producers);
        
        return $this;
    }
    
    /**
     * @param Comparison|Comparable|callable|null $comparison
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
     * @param Sorting|Comparable|callable|null $sorting
     */
    public function sort($sorting = null): Stream
    {
        $this->chainOperation(new Sort($sorting));
        return $this;
    }
    
    /**
     * Reversed (descending) sorting.
     *
     * @param Sorting|Comparable|callable|null $sorting
     */
    public function rsort($sorting = null): Stream
    {
        $this->chainOperation(new Sort(Sorting::reverse($sorting)));
        return $this;
    }
    
    /**
     * Normal sorting with limited number of {$limit} first values passed further to stream.
     *
     * @param Sorting|Comparable|callable|null $sorting
     */
    public function best(int $limit, $sorting = null): Stream
    {
        $this->chainOperation(new SortLimited($limit, $sorting));
        return $this;
    }
    
    /**
     * Reversed sorting with limited number of {$limit} values passed further to stream.
     *
     * @param Sorting|Comparable|callable|null $sorting
     */
    public function worst(int $limit, $sorting = null): Stream
    {
        $this->chainOperation(new SortLimited($limit, Sorting::reverse($sorting)));
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
        $this->chainOperation(new Shuffle($chunkSize));
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
        $this->chainOperation(new Chunk($size, $reindex));
        return $this;
    }
    
    /**
     * @param Discriminator|Condition|Predicate|Filter|Mapper|callable|array|string|int $discriminator
     */
    public function chunkBy($discriminator, bool $reindex = false): Stream
    {
        $this->chainOperation(new ChunkBy(Discriminators::prepare($discriminator), $reindex));
        return $this;
    }
    
    /**
     * @param Filter|Predicate|callable|mixed $filter
     */
    public function accumulate($filter, bool $reindex = false, int $mode = Check::VALUE): Stream
    {
        $this->chainOperation(new Accumulate($filter, $mode, $reindex));
        return $this;
    }
    
    /**
     * @param Filter|Predicate|callable|mixed $filter
     */
    public function separateBy($filter, bool $reindex = false, int $mode = Check::VALUE): Stream
    {
        $this->chainOperation(new Accumulate($filter, $mode, $reindex, true));
        return $this;
    }
    
    public function aggregate(array $keys): Stream
    {
        $this->chainOperation(new Aggregate($keys));
        return $this;
    }
    
    /**
     * @param string|int $field
     * @param Mapper|Reducer|Predicate|Filter|Discriminator|callable|array|mixed $mapper
     */
    public function append($field, $mapper): Stream
    {
        return $this->map(Mappers::append($field, $mapper));
    }
    
    /**
     * @param string|int $field
     * @param Mapper|Reducer|Predicate|Filter|Discriminator|callable|array|mixed $mapper
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
     * @param Filter|Predicate|callable|mixed $filter
     */
    public function extractWhen($filter, int $mode = Check::VALUE): Stream
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
     * @param Filter|Predicate|callable|mixed $filter
     */
    public function removeWhen($filter, int $mode = Check::VALUE): Stream
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
     * @param Mapper|Reducer|callable|array|mixed $mapper
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
            throw new \InvalidArgumentException('Empty arguments');
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
                throw new \InvalidArgumentException('Only StrimPipe is supported');
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
     * @param Discriminator|Condition|Predicate|Filter|Mapper|callable|array $discriminator
     * @param array<Stream|LastOperation|ResultApi|Collector|Consumer|Reducer> $handlers
     */
    public function dispatch($discriminator, array $handlers): Stream
    {
        foreach ($handlers as $handler) {
            if ($handler === $this) {
                throw new \LogicException('Looped message sending is not supported in Dispatch operation');
            }
        }
        
        $this->chainOperation(new Dispatch($discriminator, $handlers));
        return $this;
    }
    
    /**
     * It works like conditional limit().
     *
     * @param Filter|Predicate|callable|mixed $condition
     */
    public function while($condition, int $mode = Check::VALUE): Stream
    {
        $this->chainOperation(new Until($condition, $mode, true));
        return $this;
    }
    
    /**
     * It works like conditional limit().
     *
     * @param Filter|Predicate|callable|mixed $condition
     */
    public function until($condition, int $mode = Check::VALUE): Stream
    {
        $this->chainOperation(new Until($condition, $mode));
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
        $this->chainOperation(new Gather($reindex));
        return $this;
    }
    
    /**
     * It collects elements in array as long as they meet given condition.
     * With first element which does not meet condition, gathering values is aborted
     * and array of collected elements is passed to next step. No other items in the stream will be read.
     *
     * @param Filter|Predicate|callable|mixed $condition
     */
    public function gatherWhile($condition, bool $reindex = false, int $mode = Check::VALUE): Stream
    {
        return $this->while($condition, $mode)->gather($reindex);
    }
    
    /**
     * It collects elements in array until first element which does not meet given condition,
     * in which case gathering of values is aborted and array of collected elements is passed to next step.
     * No other items in the stream will be read.
     *
     * @param Filter|Predicate|callable|mixed $condition
     */
    public function gatherUntil($condition, bool $reindex = false, int $mode = Check::VALUE): Stream
    {
        return $this->until($condition, $mode)->gather($reindex);
    }
    
    /**
     * @param int|null $buckets null means collect all elements
     * @param Comparison|Comparable|callable|null $comparison
     */
    public function segregate(?int $buckets = null, bool $reindex = false, $comparison = null): Stream
    {
        $this->chainOperation(new Segregate($buckets, $reindex, $comparison));
        return $this;
    }
    
    /**
     * @param Discriminator|Condition|Predicate|Filter|Mapper|callable|array $discriminator
     */
    public function categorize($discriminator, ?bool $reindex = null): Stream
    {
        $this->chainOperation(new Categorize($discriminator, $reindex));
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
        $this->chainOperation(new Tuple($assoc));
        return $this;
    }
    
    /**
     * This operation works in the opposite way to makeTuple() - it maps tuple from the value
     * ([key, value] or ['key' => key, 'value' => value]) to key and value of current element.
     */
    public function unpackTuple(bool $assoc = false): Stream
    {
        $this->chainOperation(new UnpackTuple($assoc));
        return $this;
    }
    
    /**
     * Creates a numeric array where the first item comes from this stream, and the next items come from all passed
     * providers of subsequent values. In the event that any supplier runs out, null is inserted in its place.
     *
     * @param array<Stream|Producer|ResultCaster|\Traversable|\PDOStatement|callable|resource|array|scalar> $sources
     */
    public function zip(...$sources): Stream
    {
        $this->chainOperation(new Zip($sources));
        return $this;
    }
    
    /**
     * @param Stream|LastOperation|ResultApi|Collector|Consumer|Reducer $consumers
     */
    public function unzip(...$consumers): Stream
    {
        $this->chainOperation(new Unzip($consumers));
        return $this;
    }
    
    /**
     * @param Discriminator|Condition|Predicate|Filter|Mapper|callable|array $discriminator
     */
    public function fork($discriminator, LastOperation $prototype): Stream
    {
        if ($prototype instanceof ForkCollaborator) {
            $this->chainOperation(new Fork($discriminator, $prototype));
            return $this;
        }
        
        throw new \InvalidArgumentException('Only ForkCollaborator prototype is supported');
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
     * @param Comparison|Comparable|callable|null $comparison
     */
    public function accumulateUptrends(bool $reindex = false, $comparison = null): Stream
    {
        $this->chainOperation(new Uptrends($reindex, false, $comparison));
        return $this;
    }
    
    /**
     * @param Comparison|Comparable|callable|null $comparison
     */
    public function accumulateDowntrends(bool $reindex = false, $comparison = null): Stream
    {
        $this->chainOperation(new Uptrends($reindex, true, $comparison));
        return $this;
    }
    
    /**
     * @param bool $allowLimits when true then allow for limit values (first and last element in the stream)
     * @param Comparison|Comparable|callable|null $comparison
     */
    public function onlyMaxima(bool $allowLimits = true, $comparison = null): Stream
    {
        $this->chainOperation(new Maxima($allowLimits, false, $comparison));
        return $this;
    }
    
    /**
     * @param bool $allowLimits when true then allow for limit values (first and last element in the stream)
     * @param Comparison|Comparable|callable|null $comparison
     */
    public function onlyMinima(bool $allowLimits = true, $comparison = null): Stream
    {
        $this->chainOperation(new Maxima($allowLimits, true, $comparison));
        return $this;
    }
    
    /**
     * @param bool $allowLimits when true then allow for limit values (first and last element in the stream)
     * @param Comparison|Comparable|callable|null $comparison
     */
    public function onlyExtrema(bool $allowLimits = true, $comparison = null): Stream
    {
        $this->chainOperation(new Extrema($allowLimits, $comparison));
        return $this;
    }
    
    /**
     * @param Comparison|Comparable|callable|null $comparison
     */
    public function increasingTrend($comparison = null): Stream
    {
        $this->chainOperation(new Increasing(false, $comparison));
        return $this;
    }
    
    /**
     * @param Comparison|Comparable|callable|null $comparison
     */
    public function decreasingTrend($comparison = null): Stream
    {
        $this->chainOperation(new Increasing(true, $comparison));
        return $this;
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
            throw new \InvalidArgumentException('Invalid param handler');
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
    public function toJsonAssoc(int $flags = 0): string
    {
        return $this->toJson($flags, true);
    }
    
    public function toJson(int $flags = 0, bool $preserveKeys = false): string
    {
        return \json_encode($this->toArray($preserveKeys), \JSON_THROW_ON_ERROR | $flags);
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
        $this->runWith(new StoreIn($buffer, !$preserveKeys));
        
        return $buffer;
    }
    
    /**
     * Collect all elements from stream.
     */
    public function collect(bool $reindex = false): LastOperation
    {
        return $this->runLast(new Collect($this, $reindex));
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
     * @param Filter|Predicate|callable|mixed $condition
     */
    public function collectWhile($condition, int $mode = Check::VALUE): LastOperation
    {
        return $this->while($condition, $mode)->collect();
    }
    
    /**
     * It collects data as long as the condition is false and then terminates processing.
     *
     * @param Filter|Predicate|callable|mixed $condition
     */
    public function collectUntil($condition, int $mode = Check::VALUE): LastOperation
    {
        return $this->until($condition, $mode)->collect();
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
     * @param Predicate|Filter|callable|mixed $value
     */
    public function has($value, int $mode = Check::VALUE): LastOperation
    {
        return $this->runLast(new Has($this, $value, $mode));
    }
    
    public function hasAny(array $values, int $mode = Check::VALUE): LastOperation
    {
        return $this->has(Predicates::inArray($values), $mode);
    }
    
    public function hasEvery(array $values, int $mode = Check::VALUE): LastOperation
    {
        return $this->runLast(new HasEvery($this, $values, $mode));
    }
    
    public function hasOnly(array $values, int $mode = Check::VALUE): LastOperation
    {
        return $this->runLast(new HasOnly($this, $values, $mode));
    }
    
    /**
     * Return first element in stream which satisfies given predicate or null when element was not found.
     *
     * @param Predicate|Filter|callable|mixed $predicate
     */
    public function find($predicate, int $mode = Check::VALUE): LastOperation
    {
        return $this->runLast(new Find($this, $predicate, $mode));
    }
    
    /**
     * @param Predicate|Filter|callable|mixed $predicate
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
     * @param Discriminator|Condition|Predicate|Filter|Mapper|callable|array|string|int $discriminator
     */
    public function groupBy($discriminator, ?bool $reindex = null): BaseStreamCollection
    {
        $groupBy = new GroupBy($discriminator, $reindex);
        $this->runWith($groupBy);
        
        return $groupBy->result();
    }
    
    /**
     * @param Consumer|Reducer|callable|resource $consumer
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
     * Run stream pipeline.
     * Stream can be executed only once!
     */
    public function run(): void
    {
        $this->prepareToRun();
        $this->continueIteration();
        $this->finish();
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
        $this->started = true;
        $this->executed = true;
        $this->isLoop = false;
        $this->isFirstProducer = true;
        
        $this->pushToStreams = [];
        $this->onFinishHandlers = [];
        $this->onSuccessHandlers = [];
        $this->onErrorHandlers = [];
        
        $this->pipe->destroy();
        $this->stack->destroy();
        $this->source->destroy();
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
    
    protected function finish(): void
    {
        $this->executed = true;
        $this->finishSubstreems();
        
        if (!$this->signal->isError) {
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
        
        $this->pipe->prepare();
        $this->started = true;
    }
    
    protected function continueIteration(bool $once = false): bool
    {
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
            
            if ($this->pipe->head->streamingFinished($this->signal)) {
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

    protected function restartWith(Producer $producer, Operation $operation): void
    {
        $this->source->restartWith($producer, $operation);
    }
    
    protected function continueWith(Producer $producer, Operation $operation): void
    {
        $this->isFirstProducer = false;
        $this->source->continueWith($producer, $operation);
    }
    
    protected function continueFrom(Operation $operation): void
    {
        $this->source->continueFrom($operation);
    }
    
    protected function forget(Operation $operation): void
    {
        $this->source->forget($operation);
    }
    
    protected function limitReached(Operation $operation): void
    {
        $this->source->limitReached($operation);
    }
    
    /**
     * @inheritdoc
     */
    public function getIterator(): \Traversable
    {
        $this->chainOperation(new Iterate());
        
        if (\version_compare(\PHP_VERSION, '8.1.0') >= 0) {
            //@codeCoverageIgnoreStart
            return new StreamIterator81($this, $this->signal->item);
            //@codeCoverageIgnoreEnd
        } else {
            return new StreamIterator($this, $this->signal->item);
        }
    }
    
    private function chainOperation(Operation $next): Operation
    {
        if ($this->started) {
            throw new \LogicException('Cannot add operation to a stream that has already started');
        }
        
        return $this->pipe->chainOperation($next, $this);
    }
    
    protected function prepareSubstream(bool $isLoop): void
    {
        $this->source->prepareSubstream($isLoop);
    }
    
    private function finishSubstreems(): void
    {
        while (!empty($this->pushToStreams)) {
            foreach ($this->pushToStreams as $key => $stream) {
                if ($this->isLoop || !$stream->continueIteration()) {
                    unset($this->pushToStreams[$key]);
                }
            }
        }
    }
    
    protected function process(Signal $signal): bool
    {
        $this->source->setNextValue($signal->item);
        
        return $this->isLoop || $this->continueIteration($this->isFirstProducer);
    }
    
    protected function getFinalOperation(): FinalOperation
    {
        $last = $this->pipe->last;
        
        \assert($last instanceof FinalOperation, 'Houston, we have a problem');
        
        return $last;
    }
    
    protected function setSource(Source $state): void
    {
        $this->source = $state;
    }
}