<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Internal;

use FiiSoft\Jackdaw\Collector\Collector;
use FiiSoft\Jackdaw\Comparator\{Comparable, ComparatorReady};
use FiiSoft\Jackdaw\Consumer\ConsumerReady;
use FiiSoft\Jackdaw\Consumer\Consumers;
use FiiSoft\Jackdaw\Discriminator\DiscriminatorReady;
use FiiSoft\Jackdaw\Filter\FilterReady;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Internal\{Check, ForkCollaborator};
use FiiSoft\Jackdaw\Mapper\MapperReady;
use FiiSoft\Jackdaw\Mapper\Mappers;
use FiiSoft\Jackdaw\Memo\MemoWriter;
use FiiSoft\Jackdaw\Operation\Collecting\{Categorize, Fork, ForkReady, Gather, Reverse, Segregate, Sort, SortLimited,
    Tail};
use FiiSoft\Jackdaw\Operation\Filtering\{EveryNth, Extrema, FilterBy, FilterOp, FilterUntil,
    FilterWhen, FilterWhile, Increasing, Maxima, Omit, OmitBy, OmitReps, OmitWhen, Skip, SkipUntil, SkipWhile, Unique,
    Uptrends};
use FiiSoft\Jackdaw\Operation\LastOperation;
use FiiSoft\Jackdaw\Operation\Mapping\{AccumulateSeparate\Accumulate, AccumulateSeparate\Separate, Aggregate, Chunk,
    ChunkBy, Classify, Flat, Flip, Map, MapBy, MapFieldWhen, MapKey, MapKeyValue, MapWhen, MapWhileUntil\MapUntil,
    MapWhileUntil\MapWhile, Reindex, Scan, Tokenize, Tuple, UnpackTuple, Window, Zip};
use FiiSoft\Jackdaw\Operation\Operation;
use FiiSoft\Jackdaw\Operation\Sending\{CollectIn, CollectKeysIn, CountIn, Dispatch, Dispatcher\HandlerReady, Feed,
    FeedMany, Remember, RouteOne, RouteMany, SendTo, SendToMany, SendToMax, SendWhen, SendWhileUntil\SendUntil,
    SendWhileUntil\SendWhile, StoreIn, Unzip};
use FiiSoft\Jackdaw\Operation\Special\{Assert, Limit, ReadMany, ReadNext, ReadWhileUntil\ReadUntil,
    ReadWhileUntil\ReadWhile, WhileUntil\UntilTrue, WhileUntil\WhileTrue};
use FiiSoft\Jackdaw\Operation\Terminating\{Collect, CollectKeys, Count, FinalOperation, Find, First, Fold, GroupBy, Has,
    HasEvery, HasOnly, IsEmpty, Last, Reduce};
use FiiSoft\Jackdaw\Producer\ProducerReady;
use FiiSoft\Jackdaw\Reducer\Reducer;
use FiiSoft\Jackdaw\Stream;
use FiiSoft\Jackdaw\ValueRef\IntNum;
use FiiSoft\Jackdaw\ValueRef\IntProvider;

final class Operations
{
    public static function limit(int $limit): Limitable
    {
        return new Limit($limit);
    }
    
    /**
     * @param IntProvider|callable|int $offset
     */
    public static function skip($offset): Operation
    {
        return Skip::create(IntNum::getAdapter($offset));
    }
    
    /**
     * @param FilterReady|callable|mixed $filter
     */
    public static function skipWhile($filter, ?int $mode = null): PossiblyInversible
    {
        return new SkipWhile($filter, $mode);
    }
    
    /**
     * @param FilterReady|callable|mixed $filter
     */
    public static function skipUntil($filter, ?int $mode = null): PossiblyInversible
    {
        return new SkipUntil($filter, $mode);
    }
    
    /**
     * @param FilterReady|callable|mixed $filter
     */
    public static function assert($filter, ?int $mode = null): Operation
    {
        return new Assert($filter, $mode);
    }
    
    /**
     * @param FilterReady|callable|mixed $filter
     */
    public static function filter($filter, ?int $mode = null): Operation
    {
        return new FilterOp($filter, $mode);
    }
    
    /**
     * @param string|int $field
     * @param FilterReady|callable|mixed $filter
     */
    public static function filterBy($field, $filter): Operation
    {
        return new FilterBy($field, $filter);
    }
    
    /**
     * @param FilterReady|callable|mixed $condition
     * @param FilterReady|callable|mixed $filter
     */
    public static function filterWhen($condition, $filter, ?int $mode = null): Operation
    {
        return new FilterWhen($condition, $filter, $mode);
    }
    
    /**
     * @param FilterReady|callable|mixed $condition
     * @param FilterReady|callable|mixed $filter
     */
    public static function filterWhile($condition, $filter, ?int $mode = null): Operation
    {
        return new FilterWhile($condition, $filter, $mode);
    }
    
    /**
     * @param FilterReady|callable|mixed $condition
     * @param FilterReady|callable|mixed $filter
     */
    public static function filterUntil($condition, $filter, ?int $mode = null): Operation
    {
        return new FilterUntil($condition, $filter, $mode);
    }
    
    public static function filterArgs(callable $filter): Operation
    {
        return self::filter(Filters::byArgs($filter));
    }
    
    /**
     * @param FilterReady|callable|mixed $filter
     */
    public static function omit($filter, ?int $mode = null): Operation
    {
        return new Omit($filter, $mode);
    }
    
    /**
     * @param string|int $field
     * @param FilterReady|callable|mixed $filter
     */
    public static function omitBy($field, $filter): Operation
    {
        return new OmitBy($field, $filter);
    }
    
    /**
     * @param FilterReady|callable|mixed $condition
     * @param FilterReady|callable|mixed $filter
     */
    public static function omitWhen($condition, $filter, ?int $mode = null): Operation
    {
        return new OmitWhen($condition, $filter, $mode);
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
     * @param FilterReady|callable|mixed $condition
     * @param MapperReady|callable|iterable|mixed $mapper
     * @param MapperReady|callable|iterable|mixed|null $elseMapper
     */
    public static function mapWhen($condition, $mapper, $elseMapper = null): Operation
    {
        return new MapWhen($condition, $mapper, $elseMapper);
    }
    
    /**
     * @param string|int $field
     * @param FilterReady|callable|mixed $condition
     * @param MapperReady|callable|iterable|mixed $mapper
     * @param MapperReady|callable|iterable|mixed|null $elseMapper
     */
    public static function mapFieldWhen($field, $condition, $mapper, $elseMapper = null): Operation
    {
        return new MapFieldWhen($field, $condition, $mapper, $elseMapper);
    }
    
    /**
     * @param FilterReady|callable|mixed $condition
     * @param MapperReady|callable|iterable|mixed $mapper
     */
    public static function mapWhile($condition, $mapper): Operation
    {
        return new MapWhile($condition, $mapper);
    }
    
    /**
     * @param FilterReady|callable|mixed $condition
     * @param MapperReady|callable|iterable|mixed $mapper
     */
    public static function mapUntil($condition, $mapper): Operation
    {
        return new MapUntil($condition, $mapper);
    }
    
    /**
     * @param DiscriminatorReady|callable|array<string|int> $discriminator
     * @param array<string|int, MapperReady|callable|iterable|mixed> $mappers
     */
    public static function mapBy($discriminator, array $mappers): Operation
    {
        return new MapBy($discriminator, $mappers);
    }
    
    public static function mapArgs(callable $mapper): Operation
    {
        return self::map(Mappers::byArgs($mapper));
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
     * @param FilterReady|callable|mixed $condition
     * @param ConsumerReady|callable|resource $consumer
     * @param ConsumerReady|callable|resource|null $elseConsumer
     */
    public static function callWhen($condition, $consumer, $elseConsumer = null): Operation
    {
        return new SendWhen($condition, $consumer, $elseConsumer);
    }
    
    /**
     * @param FilterReady|callable|mixed $condition
     * @param ConsumerReady|callable|resource $consumer
     */
    public static function callWhile($condition, $consumer): Operation
    {
        return new SendWhile($condition, $consumer);
    }
    
    /**
     * @param FilterReady|callable|mixed $condition
     * @param ConsumerReady|callable|resource $consumer
     */
    public static function callUntil($condition, $consumer): Operation
    {
        return new SendUntil($condition, $consumer);
    }
    
    public static function callArgs(callable $consumer): Operation
    {
        return self::call(Consumers::byArgs($consumer));
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
    
    /**
     * @param IntProvider|\Traversable<int>|iterable<int>|callable|int $size
     */
    public static function chunk($size, bool $reindex = false): Operation
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
     * @param FilterReady|callable|mixed $filter
     */
    public static function accumulate($filter, bool $reindex = false, ?int $mode = null): Operation
    {
        return Accumulate::create($filter, $mode, $reindex);
    }
    
    /**
     * @param FilterReady|callable|mixed $filter
     */
    public static function separateBy($filter, bool $reindex = false, ?int $mode = null): Operation
    {
        return Separate::create($filter, $mode, $reindex);
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
    
    /**
     * @param Stream|LastOperation ...$streams
     */
    public static function feed(ForkCollaborator ...$streams): Operation
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
     * @param FilterReady|callable|mixed $condition
     */
    public static function route($condition, HandlerReady $handler): Operation
    {
        return new RouteOne($condition, $handler);
    }
    
    /**
     * @param DiscriminatorReady|callable|array<string|int> $discriminator
     * @param HandlerReady[] $handlers
     */
    public static function switch($discriminator, array $handlers): Operation
    {
        return new RouteMany($discriminator, $handlers);
    }
    
    /**
     * @param FilterReady|callable|mixed $filter
     */
    public static function while($filter, ?int $mode = null): PossiblyInversible
    {
        return new WhileTrue($filter, $mode);
    }
    
    /**
     * @param FilterReady|callable|mixed $filter
     */
    public static function until($filter, ?int $mode = null): PossiblyInversible
    {
        return new UntilTrue($filter, $mode);
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
     * @param IntProvider|\Traversable<int>|iterable<int>|callable|int $howMany
     */
    public static function readNext($howMany = 1): Operation
    {
        return new ReadNext($howMany);
    }
    
    /**
     * @param IntProvider|\Traversable<int>|iterable<int>|callable|int $howMany
     */
    public static function readMany($howMany, bool $reindex = false): Operation
    {
        return ReadMany::create($howMany, $reindex);
    }
    
    /**
     * @param FilterReady|callable|mixed $filter
     * @param ConsumerReady|callable|resource|null $consumer resource must be writeable
     */
    public static function readWhile($filter, ?int $mode = null, bool $reindex = false, $consumer = null): Operation
    {
        return new ReadWhile($filter, $mode, $reindex, $consumer);
    }
    
    /**
     * @param FilterReady|callable|mixed $filter
     * @param ConsumerReady|callable|resource|null $consumer resource must be writeable
     */
    public static function readUntil($filter, ?int $mode = null, bool $reindex = false, $consumer = null): Operation
    {
        return new ReadUntil($filter, $mode, $reindex, $consumer);
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
     * @param FilterReady|callable|mixed $predicate
     */
    public static function has(Stream $stream, $predicate, ?int $mode = null): FinalOperation
    {
        return new Has($stream, $predicate, $mode);
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
     * @param FilterReady|callable|mixed $predicate
     */
    public static function find(Stream $stream, $predicate, ?int $mode = null): FinalOperation
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