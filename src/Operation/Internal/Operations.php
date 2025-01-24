<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Internal;

use FiiSoft\Jackdaw\Collector\Collector;
use FiiSoft\Jackdaw\Comparator\{Comparable, ComparatorReady};
use FiiSoft\Jackdaw\Condition\ConditionReady;
use FiiSoft\Jackdaw\Consumer\ConsumerReady;
use FiiSoft\Jackdaw\Discriminator\DiscriminatorReady;
use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Internal\{Check, SignalHandler};
use FiiSoft\Jackdaw\Mapper\MapperReady;
use FiiSoft\Jackdaw\Memo\MemoWriter;
use FiiSoft\Jackdaw\Operation\{Collecting\Categorize, Collecting\Fork, Collecting\ForkReady, Collecting\Gather,
    Collecting\Reverse, Collecting\Segregate, Collecting\Sort, Collecting\SortLimited, Collecting\Tail,
    Filtering\EveryNth, Filtering\Extrema, Filtering\Filter as OperationFilter, Filtering\FilterBy,
    Filtering\FilterWhen, Filtering\FilterWhile, Filtering\Increasing, Filtering\Maxima, Filtering\OmitReps,
    Filtering\Skip, Filtering\SkipWhile, Filtering\Unique, Filtering\Uptrends, Mapping\Accumulate, Mapping\Aggregate,
    Mapping\Chunk, Mapping\ChunkBy, Mapping\Classify, Mapping\Flat, Mapping\Flip, Mapping\Map, Mapping\MapFieldWhen,
    Mapping\MapKey, Mapping\MapKeyValue, Mapping\MapWhen, Mapping\MapWhile, Mapping\Reindex, Mapping\Scan,
    Mapping\Tokenize, Mapping\Tuple, Mapping\UnpackTuple, Mapping\Window, Mapping\Zip, Operation, Sending\CollectIn,
    Sending\CollectKeysIn, Sending\CountIn, Sending\Dispatch, Sending\Dispatcher\HandlerReady, Sending\Feed,
    Sending\FeedMany, Sending\Remember, Sending\SendTo, Sending\SendToMany, Sending\SendToMax, Sending\SendWhen,
    Sending\SendWhile, Sending\StoreIn, Sending\Unzip, Special\Assert, Special\Limit, Special\ReadMany,
    Special\ReadManyWhile, Special\ReadNext, Special\Until, Terminating\Collect, Terminating\CollectKeys,
    Terminating\Count, Terminating\FinalOperation, Terminating\Find, Terminating\First, Terminating\Fold,
    Terminating\GroupBy, Terminating\Has, Terminating\HasEvery, Terminating\HasOnly, Terminating\IsEmpty,
    Terminating\Last, Terminating\Reduce};
use FiiSoft\Jackdaw\Producer\ProducerReady;
use FiiSoft\Jackdaw\Reducer\Reducer;
use FiiSoft\Jackdaw\Stream;
use FiiSoft\Jackdaw\ValueRef\IntProvider;

final class Operations
{
    public static function limit(int $limit): Limitable
    {
        return new Limit($limit);
    }
    
    public static function skip(int $offset): Operation
    {
        return new Skip($offset);
    }
    
    /**
     * @param Filter|callable|mixed $filter
     */
    public static function skipWhile($filter, ?int $mode = null): SkipWhile
    {
        return new SkipWhile($filter, $mode);
    }
    
    /**
     * @param Filter|callable|mixed $filter
     */
    public static function skipUntil($filter, ?int $mode = null): Operation
    {
        return new SkipWhile($filter, $mode, true);
    }
    
    /**
     * @param Filter|callable|mixed $filter
     */
    public static function assert($filter, ?int $mode = null): Operation
    {
        return new Assert($filter, $mode);
    }
    
    /**
     * @param Filter|callable|mixed $filter
     */
    public static function filter($filter, ?int $mode = null): Operation
    {
        return new OperationFilter($filter, false, $mode);
    }
    
    /**
     * @param string|int $field
     * @param Filter|callable|mixed $filter
     */
    public static function filterBy($field, $filter): Operation
    {
        return new FilterBy($field, $filter);
    }
    
    /**
     * @param ConditionReady|callable $condition
     * @param Filter|callable|mixed $filter
     */
    public static function filterWhen($condition, $filter, ?int $mode = null): Operation
    {
        return new FilterWhen($condition, $filter, false, $mode);
    }
    
    /**
     * @param ConditionReady|callable $condition
     * @param Filter|callable|mixed $filter
     */
    public static function filterWhile($condition, $filter, ?int $mode = null): Operation
    {
        return new FilterWhile($condition, $filter, $mode);
    }
    
    /**
     * @param ConditionReady|callable $condition
     * @param Filter|callable|mixed $filter
     */
    public static function filterUntil($condition, $filter, ?int $mode = null): Operation
    {
        return new FilterWhile($condition, $filter, $mode, true);
    }
    
    /**
     * @param Filter|callable|mixed $filter
     */
    public static function omit($filter, ?int $mode = null): Operation
    {
        return new OperationFilter($filter, true, $mode);
    }
    
    /**
     * @param string|int $field
     * @param Filter|callable|mixed $filter
     */
    public static function omitBy($field, $filter): Operation
    {
        return new FilterBy($field, $filter, true);
    }
    
    /**
     * @param ConditionReady|callable $condition
     * @param Filter|callable|mixed $filter
     */
    public static function omitWhen($condition, $filter, ?int $mode = null): Operation
    {
        return new FilterWhen($condition, $filter, true, $mode);
    }
    
    /**
     * @param ComparatorReady|callable|null $comparison
     */
    public static function omitReps($comparison = null): Operation
    {
        return new OmitReps($comparison);
    }
    
    /**
     * @param MapperReady|callable|iterable|mixed $mapper
     */
    public static function map($mapper): Operation
    {
        return new Map($mapper);
    }
    
    /**
     * @param ConditionReady|callable $condition
     * @param MapperReady|callable|iterable|mixed $mapper
     * @param MapperReady|callable|iterable|mixed|null $elseMapper
     */
    public static function mapWhen($condition, $mapper, $elseMapper = null): Operation
    {
        return new MapWhen($condition, $mapper, $elseMapper);
    }
    
    /**
     * @param string|int $field
     * @param ConditionReady|callable $condition
     * @param MapperReady|callable|iterable|mixed $mapper
     * @param MapperReady|callable|iterable|mixed|null $elseMapper
     */
    public static function mapFieldWhen($field, $condition, $mapper, $elseMapper = null): Operation
    {
        return new MapFieldWhen($field, $condition, $mapper, $elseMapper);
    }
    
    /**
     * @param ConditionReady|callable $condition
     * @param MapperReady|callable|iterable|mixed $mapper
     */
    public static function mapWhile($condition, $mapper): Operation
    {
        return new MapWhile($condition, $mapper);
    }
    
    /**
     * @param ConditionReady|callable $condition
     * @param MapperReady|callable|iterable|mixed $mapper
     */
    public static function mapUntil($condition, $mapper): Operation
    {
        return new MapWhile($condition, $mapper, true);
    }
    
    /**
     * @param DiscriminatorReady|callable|array<string|int> $discriminator
     */
    public static function classify($discriminator): Operation
    {
        return new Classify($discriminator);
    }
    
    /**
     * @param MapperReady|callable|iterable|mixed $mapper
     */
    public static function mapKey($mapper): MapKey
    {
        return new MapKey($mapper);
    }
    
    public static function mapKeyValue(callable $keyValueMapper): Operation
    {
        return MapKeyValue::create($keyValueMapper);
    }
    
    /**
     * @param int|null $counter REFERENCE
     */
    public static function countIn(?int &$counter): Operation
    {
        return new CountIn($counter);
    }
    
    /**
     * @param \ArrayAccess<string|int, mixed>|array<string|int, mixed> $buffer REFERENCE
     */
    public static function storeIn(&$buffer, bool $reindex = false): Operation
    {
        return StoreIn::create($buffer, $reindex);
    }
    
    /**
     * @param Collector|\ArrayAccess<string|int, mixed>|\SplHeap<mixed>|\SplPriorityQueue<int, mixed> $collector
     */
    public static function collectIn($collector, ?bool $reindex = null): Operation
    {
        return CollectIn::create($collector, $reindex);
    }
    
    /**
     * @param Collector|\ArrayAccess<string|int, mixed>|\SplHeap<mixed>|\SplPriorityQueue<int, mixed> $collector
     */
    public static function collectKeysIn($collector): Operation
    {
        return new CollectKeysIn($collector);
    }
    
    /**
     * @param ConsumerReady|callable|resource $consumers resource must be writeable
     */
    public static function call(...$consumers): Operation
    {
        return \count($consumers) === 1 ? new SendTo(...$consumers) : new SendToMany(...$consumers);
    }
    
    /**
     * @param ConsumerReady|callable|resource $consumer
     */
    public static function callMax(int $times, $consumer): Operation
    {
        return new SendToMax($times, $consumer);
    }
    
    /**
     * @param ConditionReady|callable $condition
     * @param ConsumerReady|callable|resource $consumer
     * @param ConsumerReady|callable|resource|null $elseConsumer
     */
    public static function callWhen($condition, $consumer, $elseConsumer = null): Operation
    {
        return new SendWhen($condition, $consumer, $elseConsumer);
    }
    
    /**
     * @param ConditionReady|callable $condition
     * @param ConsumerReady|callable|resource $consumer
     */
    public static function callWhile($condition, $consumer): Operation
    {
        return new SendWhile($condition, $consumer);
    }
    
    /**
     * @param ConditionReady|callable $condition
     * @param ConsumerReady|callable|resource $consumer
     */
    public static function callUntil($condition, $consumer): Operation
    {
        return new SendWhile($condition, $consumer, true);
    }
    
    /**
     * @param ComparatorReady|callable|null $comparison
     */
    public static function unique($comparison = null): Operation
    {
        return new Unique($comparison);
    }
    
    /**
     * @param Comparable|callable|null $sorting
     */
    public static function sort($sorting = null): Operation
    {
        return new Sort($sorting);
    }
    
    /**
     * @param Comparable|callable|null $sorting
     */
    public static function sortLimited(int $limit, $sorting = null): Limitable
    {
        return SortLimited::create($limit, $sorting);
    }
    
    public static function reverse(): Operation
    {
        return new Reverse();
    }
    
    public static function shuffle(?int $chunkSize = null): Operation
    {
        return Shuffle::create($chunkSize);
    }
    
    public static function reindex(int $start = 0, int $step = 1): Operation
    {
        return new Reindex($start, $step);
    }
    
    public static function flip(): Operation
    {
        return new Flip();
    }
    
    /**
     * @param mixed $initial
     * @param Reducer|callable $reducer
     */
    public static function scan($initial, $reducer): Operation
    {
        return new Scan($initial, $reducer);
    }
    
    public static function chunk(int $size, bool $reindex = false): Operation
    {
        return Chunk::create($size, $reindex);
    }
    
    /**
     * @param DiscriminatorReady|callable|array<string|int>|string|int $discriminator
     */
    public static function chunkBy($discriminator, bool $reindex = false): Operation
    {
        return ChunkBy::create($discriminator, $reindex);
    }
    
    public static function window(int $size, int $step = 1, bool $reindex = false): Operation
    {
        return new Window($size, $step, $reindex);
    }
    
    public static function everyNth(int $num): EveryNth
    {
        return new EveryNth($num);
    }
    
    /**
     * @param Filter|callable|mixed $filter
     */
    public static function accumulate($filter, bool $reindex = false, ?int $mode = null): Operation
    {
        return Accumulate::create($filter, $mode, $reindex);
    }
    
    /**
     * @param Filter|callable|mixed $filter
     */
    public static function separateBy($filter, bool $reindex = false, ?int $mode = null): Operation
    {
        return Accumulate::create($filter, $mode, $reindex, true);
    }
    
    /**
     * @param array<string|int> $keys
     */
    public static function aggregate(array $keys): Operation
    {
        return Aggregate::create($keys);
    }
    
    public static function tokenize(string $tokens = ' '): Operation
    {
        return new Tokenize($tokens);
    }
    
    public static function flat(int $level = 0): Flat
    {
        return new Flat($level);
    }
    
    public static function feed(SignalHandler ...$streams): Operation
    {
        return \count($streams) === 1 ? new Feed(...$streams) : new FeedMany(...$streams);
    }
    
    /**
     * @param DiscriminatorReady|callable|array<string|int> $discriminator
     * @param HandlerReady[] $handlers
     */
    public static function dispatch($discriminator, array $handlers): Operation
    {
        return new Dispatch($discriminator, $handlers);
    }
    
    /**
     * @param Filter|callable|mixed $filter
     */
    public static function while($filter, ?int $mode = null): Operation
    {
        return new Until($filter, $mode, true);
    }
    
    /**
     * @param Filter|callable|mixed $filter
     */
    public static function until($filter, ?int $mode = null): Until
    {
        return new Until($filter, $mode);
    }
    
    public static function tail(int $numOfItems): Operation
    {
        return new Tail($numOfItems);
    }
    
    public static function gather(bool $reindex = false): Operation
    {
        return Gather::create($reindex);
    }
    
    /**
     * @param Comparable|callable|null $comparison
     */
    public static function segregate(
        ?int $buckets = null, bool $reindex = false, $comparison = null, ?int $limit = null
    ): Limitable
    {
        return new Segregate($buckets, $reindex, $comparison, $limit);
    }
    
    /**
     * @param DiscriminatorReady|callable|array<string|int>|string|int $discriminator
     */
    public static function categorize($discriminator, ?bool $reindex = null): Operation
    {
        return Categorize::create($discriminator, $reindex);
    }
    
    public static function makeTuple(bool $assoc = false): Operation
    {
        return Tuple::create($assoc);
    }
    
    public static function unpackTuple(bool $assoc = false): Operation
    {
        return UnpackTuple::create($assoc);
    }
    
    /**
     * @param array<ProducerReady|resource|callable|iterable<string|int, mixed>|scalar> $sources
     */
    public static function zip(...$sources): Operation
    {
        return Zip::create($sources);
    }
    
    public static function unzip(HandlerReady ...$consumers): Operation
    {
        return new Unzip($consumers);
    }
    
    /**
     * @param DiscriminatorReady|callable|array<string|int> $discriminator
     */
    public static function fork($discriminator, ForkReady $prototype): Operation
    {
        return Fork::create($discriminator, $prototype);
    }
    
    public static function remember(MemoWriter $memo): Operation
    {
        return new Remember($memo);
    }
    
    /**
     * @param Comparable|callable|null $comparison
     */
    public static function accumulateUptrends(bool $reindex = false, $comparison = null): Operation
    {
        return Uptrends::create($reindex, false, $comparison);
    }
    
    /**
     * @param Comparable|callable|null $comparison
     */
    public static function accumulateDowntrends(bool $reindex = false, $comparison = null): Operation
    {
        return Uptrends::create($reindex, true, $comparison);
    }
    
    /**
     * @param Comparable|callable|null $comparison
     */
    public static function onlyMaxima(bool $allowLimits = true, $comparison = null): Operation
    {
        return new Maxima($allowLimits, false, $comparison);
    }
    
    /**
     * @param Comparable|callable|null $comparison
     */
    public static function onlyMinima(bool $allowLimits = true, $comparison = null): Operation
    {
        return new Maxima($allowLimits, true, $comparison);
    }
    
    /**
     * @param Comparable|callable|null $comparison
     */
    public static function onlyExtrema(bool $allowLimits = true, $comparison = null): Operation
    {
        return new Extrema($allowLimits, $comparison);
    }
    
    /**
     * @param Comparable|callable|null $comparison
     */
    public static function increasingTrend($comparison = null): Operation
    {
        return new Increasing(false, $comparison);
    }
    
    /**
     * @param Comparable|callable|null $comparison
     */
    public static function decreasingTrend($comparison = null): Operation
    {
        return new Increasing(true, $comparison);
    }
    
    /**
     * @param IntProvider|iterable<int>|callable|int $howMany
     */
    public static function readNext($howMany = 1): Operation
    {
        return new ReadNext($howMany);
    }
    
    /**
     * @param IntProvider|iterable<int>|callable|int $howMany
     */
    public static function readMany($howMany, bool $reindex = false): Operation
    {
        return ReadMany::create($howMany, $reindex);
    }
    
    /**
     * @param Filter|callable|mixed $filter
     * @param ConsumerReady|callable|resource|null $consumer resource must be writeable
     */
    public static function readWhile($filter, ?int $mode = null, bool $reindex = false, $consumer = null): Operation
    {
        return new ReadManyWhile($filter, $mode, $reindex, false, $consumer);
    }
    
    /**
     * @param Filter|callable|mixed $filter
     * @param ConsumerReady|callable|resource|null $consumer resource must be writeable
     */
    public static function readUntil($filter, ?int $mode = null, bool $reindex = false, $consumer = null): Operation
    {
        return new ReadManyWhile($filter, $mode, $reindex, true, $consumer);
    }
    
    public static function collect(Stream $stream, bool $reindex = false): FinalOperation
    {
        return Collect::create($stream, $reindex);
    }
    
    public static function collectKeys(Stream $stream): FinalOperation
    {
        return new CollectKeys($stream);
    }
    
    public static function count(Stream $stream): FinalOperation
    {
        return new Count($stream);
    }
    
    /**
     * @param Reducer|callable|array<Reducer|callable> $reducer
     * @param callable|mixed|null $orElse (default null)
     */
    public static function reduce(Stream $stream, $reducer, $orElse = null): FinalOperation
    {
        return new Reduce($stream, $reducer, $orElse);
    }
    
    /**
     * @param mixed $initial
     * @param Reducer|callable $reducer
     */
    public static function fold(Stream $stream, $initial, $reducer): FinalOperation
    {
        return new Fold($stream, $initial, $reducer);
    }
    
    public static function isNotEmpty(Stream $stream): FinalOperation
    {
        return new IsEmpty($stream, false);
    }
    
    public static function isEmpty(Stream $stream): FinalOperation
    {
        return new IsEmpty($stream, true);
    }
    
    /**
     * @param Filter|callable|mixed $value
     */
    public static function has(Stream $stream, $value, int $mode = Check::VALUE): FinalOperation
    {
        return new Has($stream, $value, $mode);
    }
    
    /**
     * @param array<string|int, mixed> $values
     */
    public static function hasEvery(Stream $stream, array $values, int $mode = Check::VALUE): FinalOperation
    {
        return HasEvery::create($stream, $values, $mode);
    }
    
    /**
     * @param array<string|int, mixed> $values
     */
    public static function hasOnly(Stream $stream, array $values, int $mode = Check::VALUE): FinalOperation
    {
        return HasOnly::create($stream, $values, $mode);
    }
    
    /**
     * @param Filter|callable|mixed $predicate
     */
    public static function find(Stream $stream, $predicate, int $mode = Check::VALUE): FinalOperation
    {
        return new Find($stream, $predicate, $mode);
    }
    
    /**
     * @param callable|mixed|null $orElse
     */
    public static function first(Stream $stream, $orElse = null): FinalOperation
    {
        return new First($stream, $orElse);
    }
    
    /**
     * @param callable|mixed|null $orElse
     */
    public static function last(Stream $stream, $orElse = null): FinalOperation
    {
        return new Last($stream, $orElse);
    }
    
    /**
     * @param DiscriminatorReady|callable|array<string|int>|string|int $discriminator
     */
    public static function groupBy($discriminator, ?bool $reindex = null): GroupBy
    {
        return GroupBy::create($discriminator, $reindex);
    }
}