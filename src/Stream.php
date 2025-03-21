<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw;

use FiiSoft\Jackdaw\Collector\Collector;
use FiiSoft\Jackdaw\Comparator\{Comparable, ComparatorReady, Sorting\By, Sorting\Sorting};
use FiiSoft\Jackdaw\Consumer\{ConsumerReady, Consumers};
use FiiSoft\Jackdaw\Discriminator\{DiscriminatorReady, Discriminators};
use FiiSoft\Jackdaw\Exception\{InvalidParamException, StreamExceptionFactory};
use FiiSoft\Jackdaw\Filter\{FilterReady, Filters};
use FiiSoft\Jackdaw\Handler\{ErrorHandler, OnError};
use FiiSoft\Jackdaw\Internal\{Check, Collection\BaseStreamCollection, Destroyable, Executable, Helper, Item,
    Iterator\BaseFastIterator, Iterator\BaseStreamIterator, Iterator\Interruption, Mode, Pipe, Signal, State\Source,
    State\SourceData, State\SourceNotReady, State\Sources, State\StreamSource, StreamPipe};
use FiiSoft\Jackdaw\Mapper\{Internal\ConditionalExtract, MapperReady, Mappers};
use FiiSoft\Jackdaw\Memo\MemoWriter;
use FiiSoft\Jackdaw\Operation\{Internal\Operations, LastOperation, Operation};
use FiiSoft\Jackdaw\Operation\Collecting\ForkReady;
use FiiSoft\Jackdaw\Operation\Sending\Dispatcher\HandlerReady;
use FiiSoft\Jackdaw\Operation\Special\{Assert\AssertionFailed, Iterate};
use FiiSoft\Jackdaw\Producer\{Internal\EmptyProducer, MultiProducer, Producer, ProducerReady, Producers};
use FiiSoft\Jackdaw\Reducer\Reducer;
use FiiSoft\Jackdaw\ValueRef\IntProvider;

/**
 * @implements \IteratorAggregate<string|int, mixed>
 */
final class Stream extends StreamSource
    implements ProducerReady, HandlerReady, Executable, Destroyable, \IteratorAggregate
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
    
    /** @var StreamPipe[] */
    private array $pushToStreams = [];
    
    /** @var callable[] */
    private array $onFinishHandlers = [];
    
    /** @var callable[] */
    private array $onSuccessHandlers = [];
    
    /** @var ErrorHandler[] */
    private array $onErrorHandlers = [];
    
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
        $this->chainOperation(Operations::limit($limit));
        return $this;
    }
    
    /**
     * @param IntProvider|callable|int $offset
     */
    public function skip($offset): Stream
    {
        $this->chainOperation(Operations::skip($offset));
        return $this;
    }
    
    /**
     * @param FilterReady|callable|mixed $filter
     */
    public function skipWhile($filter, ?int $mode = null): Stream
    {
        $this->chainOperation(Operations::skipWhile($filter, $mode));
        return $this;
    }
    
    /**
     * @param FilterReady|callable|mixed $filter
     */
    public function skipUntil($filter, ?int $mode = null): Stream
    {
        $this->chainOperation(Operations::skipUntil($filter, $mode));
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
     * @param FilterReady|callable|mixed $filter
     * @throws AssertionFailed
     */
    public function assert($filter, ?int $mode = null): Stream
    {
        $this->chainOperation(Operations::assert($filter, $mode));
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
     * @param FilterReady|callable|mixed $filter
     */
    public function filterBy($field, $filter): Stream
    {
        $this->chainOperation(Operations::filterBy($field, $filter));
        return $this;
    }
    
    /**
     * @param FilterReady|callable|mixed $filter
     */
    public function filter($filter, ?int $mode = null): Stream
    {
        $this->chainOperation(Operations::filter($filter, $mode));
        return $this;
    }
    
    /**
     * @param FilterReady|callable|mixed $condition
     * @param FilterReady|callable|mixed $filter
     */
    public function filterWhen($condition, $filter, ?int $mode = null): Stream
    {
        $this->chainOperation(Operations::filterWhen($condition, $filter, $mode));
        return $this;
    }
    
    /**
     * @param FilterReady|callable|mixed $condition
     * @param FilterReady|callable|mixed $filter
     */
    public function filterWhile($condition, $filter, ?int $mode = null): Stream
    {
        $this->chainOperation(Operations::filterWhile($condition, $filter, $mode));
        return $this;
    }
    
    /**
     * @param FilterReady|callable|mixed $condition
     * @param FilterReady|callable|mixed $filter
     */
    public function filterUntil($condition, $filter, ?int $mode = null): Stream
    {
        $this->chainOperation(Operations::filterUntil($condition, $filter, $mode));
        return $this;
    }
    
    public function filterArgs(callable $filter): Stream
    {
        $this->chainOperation(Operations::filterArgs($filter));
        return $this;
    }
    
    /**
     * @param FilterReady|callable|mixed $filter
     */
    public function filterKey($filter): Stream
    {
        return $this->filter($filter, Check::KEY);
    }
    
    /**
     * @param string|int $field
     * @param FilterReady|callable|mixed $filter
     */
    public function omitBy($field, $filter): Stream
    {
        $this->chainOperation(Operations::omitBy($field, $filter));
        return $this;
    }
    
    /**
     * @param FilterReady|callable|mixed $filter
     */
    public function omit($filter, ?int $mode = null): Stream
    {
        $this->chainOperation(Operations::omit($filter, $mode));
        return $this;
    }
    
    /**
     * @param FilterReady|callable|mixed $condition
     * @param FilterReady|callable|mixed $filter
     */
    public function omitWhen($condition, $filter, ?int $mode = null): Stream
    {
        $this->chainOperation(Operations::omitWhen($condition, $filter, $mode));
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
        $this->chainOperation(Operations::omitReps($comparison));
        return $this;
    }
    
    /**
     * @param FilterReady|callable|mixed $filter
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
        $this->chainOperation(Operations::map($mapper));
        return $this;
    }
    
    /**
     * @param FilterReady|callable|mixed $condition
     * @param MapperReady|callable|iterable|mixed $mapper
     * @param MapperReady|callable|iterable|mixed|null $elseMapper
     */
    public function mapWhen($condition, $mapper, $elseMapper = null): Stream
    {
        $this->chainOperation(Operations::mapWhen($condition, $mapper, $elseMapper));
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
     * @param FilterReady|callable|mixed $condition
     * @param MapperReady|callable|iterable|mixed $mapper
     * @param MapperReady|callable|iterable|mixed|null $elseMapper
     */
    public function mapFieldWhen($field, $condition, $mapper, $elseMapper = null): Stream
    {
        $this->chainOperation(Operations::mapFieldWhen($field, $condition, $mapper, $elseMapper));
        return $this;
    }
    
    /**
     * @param FilterReady|callable|mixed $condition
     * @param MapperReady|callable|iterable|mixed $mapper
     */
    public function mapWhile($condition, $mapper): Stream
    {
        $this->chainOperation(Operations::mapWhile($condition, $mapper));
        return $this;
    }
    
    /**
     * @param FilterReady|callable|mixed $condition
     * @param MapperReady|callable|iterable|mixed $mapper
     */
    public function mapUntil($condition, $mapper): Stream
    {
        $this->chainOperation(Operations::mapUntil($condition, $mapper));
        return $this;
    }
    
    /**
     * @param DiscriminatorReady|callable|array<string|int> $discriminator
     * @param array<string|int, MapperReady|callable|iterable|mixed> $mappers
     */
    public function mapBy($discriminator, array $mappers): Stream
    {
        $this->chainOperation(Operations::mapBy($discriminator, $mappers));
        return $this;
    }
    
    public function mapArgs(callable $mapper): Stream
    {
        $this->chainOperation(Operations::mapArgs($mapper));
        return $this;
    }
    
    /**
     * It works very similarly to mapKey - the difference is that it uses Discriminator as mapper
     * and guarantees that key is string, int or bool.
     *
     * @param DiscriminatorReady|callable|array<string|int> $discriminator
     */
    public function classify($discriminator): Stream
    {
        $this->chainOperation(Operations::classify($discriminator));
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
        $this->chainOperation(Operations::mapKey($mapper));
        return $this;
    }
    
    /**
     * This is specialized map operation which maps both key and value at the same time.
     * Callable $factory can accept zero, one (value) or two (value, key) params and MUST return array
     * with exactly one element - new pair of [key => value] passed to next step in stream.
     */
    public function mapKV(callable $keyValueMapper): Stream
    {
        $this->chainOperation(Operations::mapKeyValue($keyValueMapper));
        return $this;
    }
    
    /**
     * Each time the signal reaches this operation, the value of passed variable is increased by 1.
     *
     * @param int|null $counter REFERENCE is set to 0 when NULL during initialization
     */
    public function countIn(?int &$counter): Stream
    {
        $this->chainOperation(Operations::countIn($counter));
        return $this;
    }
    
    /**
     * It works in a similar way to method collectIn() and allows to store values with keys
     * in given array $buffer instead of Collector.
     *
     * @param \ArrayAccess<string|int, mixed>|array<string|int, mixed> $buffer REFERENCE
     */
    public function storeIn(&$buffer, bool $reindex = false): Stream
    {
        $this->chainOperation(Operations::storeIn($buffer, $reindex));
        return $this;
    }
    
    /**
     * @param Collector|\ArrayAccess<string|int, mixed>|\SplHeap<mixed>|\SplPriorityQueue<int, mixed> $collector
    */
    public function collectIn($collector, ?bool $reindex = null): Stream
    {
        $this->chainOperation(Operations::collectIn($collector, $reindex));
        return $this;
    }
    
    /**
     * @param Collector|\ArrayAccess<string|int, mixed>|\SplHeap<mixed>|\SplPriorityQueue<int, mixed> $collector
     */
    public function collectKeysIn($collector): Stream
    {
        $this->chainOperation(Operations::collectKeysIn($collector));
        return $this;
    }
    
    /**
     * @param ConsumerReady|callable|resource $consumers resource must be writeable
     */
    public function call(...$consumers): Stream
    {
        $this->chainOperation(Operations::call(...$consumers));
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
        $this->chainOperation(Operations::callMax($times, $consumer));
        return $this;
    }
    
    /**
     * @param FilterReady|callable|mixed $condition
     * @param ConsumerReady|callable|resource $consumer
     * @param ConsumerReady|callable|resource|null $elseConsumer
     */
    public function callWhen($condition, $consumer, $elseConsumer = null): Stream
    {
        $this->chainOperation(Operations::callWhen($condition, $consumer, $elseConsumer));
        return $this;
    }
    
    /**
     * @param FilterReady|callable|mixed $condition
     * @param ConsumerReady|callable|resource $consumer
     */
    public function callWhile($condition, $consumer): Stream
    {
        $this->chainOperation(Operations::callWhile($condition, $consumer));
        return $this;
    }
    
    /**
     * @param FilterReady|callable|mixed $condition
     * @param ConsumerReady|callable|resource $consumer
     */
    public function callUntil($condition, $consumer): Stream
    {
        $this->chainOperation(Operations::callUntil($condition, $consumer));
        return $this;
    }
    
    public function callArgs(callable $consumer): Stream
    {
        $this->chainOperation(Operations::callArgs($consumer));
        return $this;
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
     * @param ProducerReady|resource|callable|iterable<string|int, mixed>|string ...$producers
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
        $this->chainOperation(Operations::unique($comparison));
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
        $this->chainOperation(Operations::sort($sorting));
        return $this;
    }
    
    /**
     * Reversed (descending) sorting.
     *
     * @param Comparable|callable|null $sorting
     */
    public function rsort($sorting = null): Stream
    {
        $this->sort(Sorting::reverse($sorting));
        return $this;
    }
    
    /**
     * Normal sorting with limited number of {$limit} first values passed further to stream.
     *
     * @param Comparable|callable|null $sorting
     */
    public function best(int $limit, $sorting = null): Stream
    {
        $this->chainOperation(Operations::sortLimited($limit, $sorting));
        return $this;
    }
    
    /**
     * Reversed sorting with limited number of {$limit} values passed further to stream.
     *
     * @param Comparable|callable|null $sorting
     */
    public function worst(int $limit, $sorting = null): Stream
    {
        $this->best($limit, Sorting::reverse($sorting));
        return $this;
    }
    
    /**
     * Collect all incoming elements from stream and when there are no more elements,
     * reverse their order and start streaming again.
     */
    public function reverse(): Stream
    {
        $this->chainOperation(Operations::reverse());
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
        $this->chainOperation(Operations::shuffle($chunkSize));
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
        $this->chainOperation(Operations::reindex($start, $step));
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
        $this->chainOperation(Operations::flip());
        return $this;
    }
    
    /**
     * @param mixed $initial
     * @param Reducer|callable $reducer
     */
    public function scan($initial, $reducer): Stream
    {
        $this->chainOperation(Operations::scan($initial, $reducer));
        return $this;
    }
    
    /**
     * @param IntProvider|\Traversable<int>|iterable<int>|callable|int $size
     */
    public function chunk($size, bool $reindex = false): Stream
    {
        $this->chainOperation(Operations::chunk($size, $reindex));
        return $this;
    }
    
    /**
     * @param DiscriminatorReady|callable|array<string|int>|string|int $discriminator
     */
    public function chunkBy($discriminator, bool $reindex = false): Stream
    {
        $this->chainOperation(Operations::chunkBy($discriminator, $reindex));
        return $this;
    }
    
    public function window(int $size, int $step = 1, bool $reindex = false): Stream
    {
        $this->chainOperation(Operations::window($size, $step, $reindex));
        return $this;
    }
    
    public function everyNth(int $num): Stream
    {
        $this->chainOperation(Operations::everyNth($num));
        return $this;
    }
    
    public function skipNth(int $num): Stream
    {
        $this->chainOperation(Operations::skipNth($num));
        return $this;
    }
    
    /**
     * @param FilterReady|callable|mixed $filter
     */
    public function accumulate($filter, bool $reindex = false, ?int $mode = null): Stream
    {
        $this->chainOperation(Operations::accumulate($filter, $reindex, $mode));
        return $this;
    }
    
    /**
     * @param FilterReady|callable|mixed $filter
     */
    public function separateBy($filter, bool $reindex = false, ?int $mode = null): Stream
    {
        $this->chainOperation(Operations::separateBy($filter, $reindex, $mode));
        return $this;
    }
    
    /**
     * @param array<string|int> $keys
     */
    public function aggregate(array $keys): Stream
    {
        $this->chainOperation(Operations::aggregate($keys));
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
     * @param array<string|int>|string|int $fields
     * @param mixed|null $orElse
     */
    public function extract($fields, $orElse = null): Stream
    {
        return $this->map(Mappers::extract($fields, $orElse));
    }
    
    /**
     * @param FilterReady|callable|mixed $filter
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
     * @param FilterReady|callable|mixed $filter
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
        $this->chainOperation(Operations::tokenize($tokens));
        return $this;
    }
    
    public function flat(int $level = 0): Stream
    {
        $this->chainOperation(Operations::flat($level));
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
     * @param Stream|LastOperation ...$streams
     */
    public function feed(StreamPipe ...$streams): Stream
    {
        foreach ($streams as $stream) {
            $id = \spl_object_id($stream);
            
            if (!isset($this->pushToStreams[$id])) {
                $this->pushToStreams[$id] = $stream;
                
                if ($stream === $this) {
                    $this->isLoop = true;
                }
                
                $stream->prepareSubstream($this->isLoop);
            }
        }
        
        $this->chainOperation(Operations::feed(...$streams));
        
        return $this;
    }
    
    /**
     * @param DiscriminatorReady|callable|array<string|int> $discriminator
     * @param HandlerReady[] $handlers
     */
    public function dispatch($discriminator, array $handlers): Stream
    {
        foreach ($handlers as $handler) {
            if ($handler === $this) {
                throw StreamExceptionFactory::dispatchOperationCannotHandleLoops();
            }
        }
        
        $this->chainOperation(Operations::dispatch($discriminator, $handlers));
        
        return $this;
    }
    
    /**
     * @param FilterReady|callable|mixed $condition
     */
    public function route($condition, HandlerReady $handler): Stream
    {
        $this->chainOperation(Operations::route($condition, $handler));
        return $this;
    }
    
    /**
     * @param DiscriminatorReady|callable|array<string|int> $discriminator
     * @param HandlerReady[] $handlers
     */
    public function switch($discriminator, array $handlers): Stream
    {
        $this->chainOperation(Operations::switch($discriminator, $handlers));
        return $this;
    }
    
    /**
     * It works like conditional limit().
     *
     * @param FilterReady|callable|mixed $filter
     */
    public function while($filter, ?int $mode = null): Stream
    {
        $this->chainOperation(Operations::while($filter, $mode));
        return $this;
    }
    
    /**
     * It works like conditional limit().
     *
     * @param FilterReady|callable|mixed $filter
     */
    public function until($filter, ?int $mode = null): Stream
    {
        $this->chainOperation(Operations::until($filter, $mode));
        return $this;
    }
    
    /**
     * @param int $numOfItems number of Nth last elements
     */
    public function tail(int $numOfItems): Stream
    {
        $this->chainOperation(Operations::tail($numOfItems));
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
        $this->chainOperation(Operations::gather($reindex));
        return $this;
    }
    
    /**
     * It collects elements in array as long as they meet given condition.
     * With first element which does not meet condition, gathering values is aborted
     * and array of collected elements is passed to next step. No other items in the stream will be read.
     *
     * @param FilterReady|callable|mixed $filter
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
     * @param FilterReady|callable|mixed $filter
     */
    public function gatherUntil($filter, bool $reindex = false, ?int $mode = null): Stream
    {
        return $this->until($filter, $mode)->gather($reindex);
    }
    
    /**
     * @param int|null $buckets null means collect all elements
     * @param Comparable|callable|null $comparison
     * @param int|null $limit max number of collected elements in each bucket; null means no limits
     */
    public function segregate(
        ?int $buckets = null, bool $reindex = false, $comparison = null, ?int $limit = null
    ): Stream
    {
        $this->chainOperation(Operations::segregate($buckets, $reindex, $comparison, $limit));
        return $this;
    }
    
    /**
     * @param DiscriminatorReady|callable|array<string|int>|string|int $discriminator
     */
    public function categorize($discriminator, ?bool $reindex = null): Stream
    {
        $this->chainOperation(Operations::categorize($discriminator, $reindex));
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
     * Syntactic sugar for $stream->categorize(Disriminators::byKey()).
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
        $this->chainOperation(Operations::makeTuple($assoc));
        return $this;
    }
    
    /**
     * This operation works in the opposite way to makeTuple() - it maps tuple from the value
     * ([key, value] or ['key' => key, 'value' => value]) to key and value of current element.
     */
    public function unpackTuple(bool $assoc = false): Stream
    {
        $this->chainOperation(Operations::unpackTuple($assoc));
        return $this;
    }
    
    /**
     * Creates a numeric array where the first item comes from this stream, and the next items come from all passed
     * providers of subsequent values. In the event that any supplier runs out, null is inserted in its place.
     *
     * @param array<ProducerReady|resource|callable|iterable<string|int, mixed>|scalar> $sources
     */
    public function zip(...$sources): Stream
    {
        $this->chainOperation(Operations::zip(...$sources));
        return $this;
    }
    
    public function unzip(HandlerReady ...$consumers): Stream
    {
        $this->chainOperation(Operations::unzip(...$consumers));
        return $this;
    }
    
    /**
     * @param DiscriminatorReady|callable|array<string|int> $discriminator
     */
    public function fork($discriminator, ForkReady $prototype): Stream
    {
        $this->chainOperation(Operations::fork($discriminator, $prototype));
        return $this;
    }
    
    /**
     * @param string|int $field
     */
    public function forkBy($field, ForkReady $prototype): Stream
    {
        return $this->fork(Discriminators::byField($field), $prototype);
    }
    
    /**
     * @param DiscriminatorReady|callable|array<string|int> $discriminator
     * @param array<string|int, ForkReady> $handlers
     */
    public function forkMatch($discriminator, array $handlers, ?ForkReady $prototype = null): Stream
    {
        $this->chainOperation(Operations::forkMatch($discriminator, $handlers, $prototype));
        return $this;
    }
    
    /**
     * Remember key and/or value of current element in passed writer.
     */
    public function remember(MemoWriter $memo): Stream
    {
        $this->chainOperation(Operations::remember($memo));
        return $this;
    }
    
    /**
     * @param Comparable|callable|null $comparison
     */
    public function accumulateUptrends(bool $reindex = false, $comparison = null): Stream
    {
        $this->chainOperation(Operations::accumulateUptrends($reindex, $comparison));
        return $this;
    }
    
    /**
     * @param Comparable|callable|null $comparison
     */
    public function accumulateDowntrends(bool $reindex = false, $comparison = null): Stream
    {
        $this->chainOperation(Operations::accumulateDowntrends($reindex, $comparison));
        return $this;
    }
    
    /**
     * @param bool $allowLimits when true then allow for limit values (first and last element in the stream)
     * @param Comparable|callable|null $comparison
     */
    public function onlyMaxima(bool $allowLimits = true, $comparison = null): Stream
    {
        $this->chainOperation(Operations::onlyMaxima($allowLimits, $comparison));
        return $this;
    }
    
    /**
     * @param bool $allowLimits when true then allow for limit values (first and last element in the stream)
     * @param Comparable|callable|null $comparison
     */
    public function onlyMinima(bool $allowLimits = true, $comparison = null): Stream
    {
        $this->chainOperation(Operations::onlyMinima($allowLimits, $comparison));
        return $this;
    }
    
    /**
     * @param bool $allowLimits when true then allow for limit values (first and last element in the stream)
     * @param Comparable|callable|null $comparison
     */
    public function onlyExtrema(bool $allowLimits = true, $comparison = null): Stream
    {
        $this->chainOperation(Operations::onlyExtrema($allowLimits, $comparison));
        return $this;
    }
    
    /**
     * @param Comparable|callable|null $comparison
     */
    public function increasingTrend($comparison = null): Stream
    {
        $this->chainOperation(Operations::increasingTrend($comparison));
        return $this;
    }
    
    /**
     * @param Comparable|callable|null $comparison
     */
    public function decreasingTrend($comparison = null): Stream
    {
        $this->chainOperation(Operations::decreasingTrend($comparison));
        return $this;
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
        $this->chainOperation(Operations::readNext($howMany));
        return $this;
    }
    
    /**
     * @param IntProvider|\Traversable<int>|iterable<int>|callable|int $howMany how many elements should be read
     *                                                        from the stream; every read element will be passed down
     */
    public function readMany($howMany, bool $reindex = false): Stream
    {
        $this->chainOperation(Operations::readMany($howMany, $reindex));
        return $this;
    }
    
    /**
     * @param FilterReady|callable|mixed $filter
     * @param ConsumerReady|callable|resource|null $consumer resource must be writeable
     */
    public function readWhile($filter, ?int $mode = null, bool $reindex = false, $consumer = null): Stream
    {
        $this->chainOperation(Operations::readWhile($filter, $mode, $reindex, $consumer));
        return $this;
    }
    
    /**
     * @param FilterReady|callable|mixed $filter
     * @param ConsumerReady|callable|resource|null $consumer resource must be writeable
     */
    public function readUntil($filter, ?int $mode = null, bool $reindex = false, $consumer = null): Stream
    {
        $this->chainOperation(Operations::readUntil($filter, $mode, $reindex, $consumer));
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
        $buffer = [];
        $this->runWith(Operations::storeIn($buffer, !$preserveKeys));
        
        return $buffer;
    }
    
    /**
     * Collect all elements from stream.
     */
    public function collect(bool $reindex = false): LastOperation
    {
        return $this->runLast(Operations::collect($this, $reindex));
    }
    
    /**
     * Collect all keys from stream.
     */
    public function collectKeys(): LastOperation
    {
        return $this->runLast(Operations::collectKeys($this));
    }
    
    /**
     * It collects data as long as the condition is true and then terminates processing when it is not.
     *
     * @param FilterReady|callable|mixed $filter
     */
    public function collectWhile($filter, ?int $mode = null): LastOperation
    {
        return $this->while($filter, $mode)->collect();
    }
    
    /**
     * @param FilterReady|callable|mixed $filter
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
        return $this->runLast(Operations::count($this));
    }
    
    /**
     * @param Reducer|callable|array<Reducer|callable> $reducer
     * @param callable|mixed|null $orElse (default null)
     */
    public function reduce($reducer, $orElse = null): LastOperation
    {
        return $this->runLast(Operations::reduce($this, $reducer, $orElse));
    }
    
    /**
     * @param mixed $initial
     * @param Reducer|callable $reducer Callable accepts two arguments: accumulator and current value
     */
    public function fold($initial, $reducer): LastOperation
    {
        return $this->runLast(Operations::fold($this, $initial, $reducer));
    }
    
    /**
     * Tell if stream is not empty.
     */
    public function isNotEmpty(): LastOperation
    {
        return $this->runLast(Operations::isNotEmpty($this));
    }
    
    /**
     * Tell if stream is empty.
     */
    public function isEmpty(): LastOperation
    {
        return $this->runLast(Operations::isEmpty($this));
    }
    
    /**
     * Tell if element occurs in stream.
     *
     * @param FilterReady|callable|mixed $value
     */
    public function has($value, ?int $mode = null): LastOperation
    {
        return $this->runLast(Operations::has($this, $value, $mode));
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
        return $this->runLast(Operations::hasEvery($this, $values, $mode));
    }
    
    /**
     * @param array<string|int, mixed> $values
     */
    public function hasOnly(array $values, int $mode = Check::VALUE): LastOperation
    {
        return $this->runLast(Operations::hasOnly($this, $values, $mode));
    }
    
    /**
     * Return first element in stream which satisfies given predicate or null when element was not found.
     *
     * @param FilterReady|callable|mixed $predicate
     */
    public function find($predicate, ?int $mode = null): LastOperation
    {
        return $this->runLast(Operations::find($this, $predicate, $mode));
    }
    
    /**
     * @param FilterReady|callable|mixed $predicate
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
        return $this->runLast(Operations::first($this));
    }
    
    /**
     * Return first available element from stream or default when stream is empty.
     *
     * @param callable|mixed|null $orElse
     */
    public function firstOrElse($orElse): LastOperation
    {
        return $this->runLast(Operations::first($this, $orElse));
    }
    
    /**
     * Return last element from stream or null when stream is empty.
     */
    public function last(): LastOperation
    {
        return $this->runLast(Operations::last($this));
    }
    
    /**
     * Return last element from stream or default when stream is empty.
     *
     * @param callable|mixed|null $orElse
     */
    public function lastOrElse($orElse): LastOperation
    {
        return $this->runLast(Operations::last($this, $orElse));
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
        $this->chainOperation($operation);
        $this->run();
    }
    
    private function runLast(Operation $operation): LastOperation
    {
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
    public function run(bool $onlyIfNotRunYet = false): void
    {
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
        
        if ($this->signal->isWorking) {
            $item = $this->signal->item;
            
            foreach (Producers::getAdapter($producer) as $item->key => $item->value) {
                if ($this->pushTheItemThroughThePipe()) {
                    break;
                }
            }
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
            
            return BaseFastIterator::create($this, $this->pipe->buildStream($this->producer));
        }
        
        $this->chainOperation(new Iterate());
        $this->initialize();
        
        return BaseStreamIterator::create($this, $this->signal->item);
    }
    
    private function canBuildPowerStream(): bool
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
    
    /**
     * @return bool returns true when stops working
     */
    private function pushTheItemThroughThePipe(): bool
    {
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
                    return false;
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
                    return false;
                }
                
                goto ITERATION_LOOP;
            }
        }
        
        return true;
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
}