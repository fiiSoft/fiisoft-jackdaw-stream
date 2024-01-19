<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Collector\Collectors;
use FiiSoft\Jackdaw\Comparator\Comparison\Compare;
use FiiSoft\Jackdaw\Consumer\Consumers;
use FiiSoft\Jackdaw\Discriminator\Discriminators;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Exception\PipeExceptionFactory;
use FiiSoft\Jackdaw\Internal\Pipe;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Mapper\Mappers;
use FiiSoft\Jackdaw\Operation\Collecting\Categorize;
use FiiSoft\Jackdaw\Operation\Collecting\Gather;
use FiiSoft\Jackdaw\Operation\Collecting\Reverse;
use FiiSoft\Jackdaw\Operation\Collecting\Segregate;
use FiiSoft\Jackdaw\Operation\Collecting\ShuffleAll;
use FiiSoft\Jackdaw\Operation\Collecting\Sort;
use FiiSoft\Jackdaw\Operation\Collecting\SortLimited;
use FiiSoft\Jackdaw\Operation\Collecting\Tail;
use FiiSoft\Jackdaw\Operation\Exception\OperationExceptionFactory;
use FiiSoft\Jackdaw\Operation\Filtering\EveryNth;
use FiiSoft\Jackdaw\Operation\Filtering\Filter;
use FiiSoft\Jackdaw\Operation\Filtering\FilterBy;
use FiiSoft\Jackdaw\Operation\Filtering\FilterByMany;
use FiiSoft\Jackdaw\Operation\Filtering\FilterMany;
use FiiSoft\Jackdaw\Operation\Mapping\Flip;
use FiiSoft\Jackdaw\Operation\Filtering\OmitReps;
use FiiSoft\Jackdaw\Operation\Filtering\Skip;
use FiiSoft\Jackdaw\Operation\Filtering\SkipWhile;
use FiiSoft\Jackdaw\Operation\Filtering\Unique;
use FiiSoft\Jackdaw\Operation\Filtering\Uptrends;
use FiiSoft\Jackdaw\Operation\Internal\Pipe\Ending;
use FiiSoft\Jackdaw\Operation\Internal\Pipe\Initial;
use FiiSoft\Jackdaw\Operation\Internal\Shuffle;
use FiiSoft\Jackdaw\Operation\Mapping\Accumulate;
use FiiSoft\Jackdaw\Operation\Mapping\Aggregate;
use FiiSoft\Jackdaw\Operation\Mapping\Chunk;
use FiiSoft\Jackdaw\Operation\Mapping\ChunkBy;
use FiiSoft\Jackdaw\Operation\Mapping\Classify;
use FiiSoft\Jackdaw\Operation\Mapping\Flat;
use FiiSoft\Jackdaw\Operation\Mapping\Map;
use FiiSoft\Jackdaw\Operation\Mapping\MapFieldWhen;
use FiiSoft\Jackdaw\Operation\Mapping\MapKey;
use FiiSoft\Jackdaw\Operation\Mapping\MapKeyValue;
use FiiSoft\Jackdaw\Operation\Mapping\MapMany;
use FiiSoft\Jackdaw\Operation\Mapping\MapWhen;
use FiiSoft\Jackdaw\Operation\Mapping\Reindex;
use FiiSoft\Jackdaw\Operation\Mapping\Scan;
use FiiSoft\Jackdaw\Operation\Mapping\Tokenize;
use FiiSoft\Jackdaw\Operation\Mapping\Tuple;
use FiiSoft\Jackdaw\Operation\Mapping\UnpackTuple;
use FiiSoft\Jackdaw\Operation\Mapping\Window;
use FiiSoft\Jackdaw\Operation\Operation;
use FiiSoft\Jackdaw\Operation\Sending\CollectIn;
use FiiSoft\Jackdaw\Operation\Sending\Feed;
use FiiSoft\Jackdaw\Operation\Sending\FeedMany;
use FiiSoft\Jackdaw\Operation\Sending\SendTo;
use FiiSoft\Jackdaw\Operation\Sending\SendToMany;
use FiiSoft\Jackdaw\Operation\Special\Limit;
use FiiSoft\Jackdaw\Operation\Special\ShuffleChunks;
use FiiSoft\Jackdaw\Operation\Special\Until;
use FiiSoft\Jackdaw\Operation\Terminating\Collect;
use FiiSoft\Jackdaw\Operation\Terminating\CollectKeys;
use FiiSoft\Jackdaw\Operation\Terminating\Count;
use FiiSoft\Jackdaw\Operation\Terminating\Find;
use FiiSoft\Jackdaw\Operation\Terminating\First;
use FiiSoft\Jackdaw\Operation\Terminating\Has;
use FiiSoft\Jackdaw\Operation\Terminating\HasEvery;
use FiiSoft\Jackdaw\Operation\Terminating\HasOnly;
use FiiSoft\Jackdaw\Operation\Terminating\IsEmpty;
use FiiSoft\Jackdaw\Operation\Terminating\Last;
use FiiSoft\Jackdaw\Producer\Generator\Flattener;
use FiiSoft\Jackdaw\Producer\Producers;
use FiiSoft\Jackdaw\Reducer\Reducers;
use FiiSoft\Jackdaw\Stream;
use PHPUnit\Framework\TestCase;

final class PipeTest extends TestCase
{
    /**
     * @dataProvider getDataForTestGeneralChainOperations
     */
    public function test_general_chain_operations(...$testData): void
    {
        //prepare
        $operations = $expected = [];
        
        foreach ($testData as $item) {
            if (\is_object($item)) {
                $operations[] = $item;
            } else {
                $expected[] = $item;
            }
        }
        
        //given
        $stream = Stream::empty();
        $pipe = $this->getPipeFromStream($stream);
        
        //when
        $this->chainOperations($pipe, $stream, ...$operations);
        
        //then
        $this->assertPipeContainsOperations($pipe, ...$expected);
    }
    
    public static function getDataForTestGeneralChainOperations(): \Generator
    {
        $stream = Stream::empty();
        $mapper = Mappers::getAdapter('strtolower');
        $discriminator = Discriminators::getAdapter('is_string');
        
        yield 'Accumulate_Reindex_custom' => [
            Accumulate::create('is_subclass_of'), new Reindex(1), Accumulate::class, Reindex::class
        ];
        yield 'Accumulate_Reindex_default' => [Accumulate::create('is_string'), new Reindex(), Accumulate::class];
        
        yield 'Aggregate_Reindex_custom' => [
            Aggregate::create(['a']), new Reindex(1), Aggregate::class, Reindex::class
        ];
        yield 'Aggregate_Reindex_default' => [Aggregate::create(['a']), new Reindex(), Aggregate::class];
        
        yield 'Chunk_Flat_1' => [Chunk::create(1), new Flat(1)];
        yield 'Chunk_Flat_3' => [Chunk::create(2, true), new Flat(1), Chunk::class, Flat::class];
        yield 'Chunk_Flat_2' => [Chunk::create(3), new Flat(), Flat::class];
        yield 'Chunk_Flat_4' => [Chunk::create(4, true), new Flat(), Chunk::class, Flat::class];
        yield 'Chunk_Reindex_custom' => [Chunk::create(2), new Reindex(1), Chunk::class, Reindex::class];
        yield 'Chunk_Reindex_default' => [Chunk::create(2), new Reindex(), Chunk::class];
        
        yield 'EveryNth_1' => [new EveryNth(1)];
        yield 'EveryNth_2' => [new EveryNth(2), EveryNth::class];
        yield 'EveryNth_EveryNth' => [new EveryNth(2), new EveryNth(3), EveryNth::class];
        
        yield 'Feed_Feed' => [new Feed($stream), new Feed($stream), FeedMany::class];
        
        yield 'Filter_First' => [new Filter('is_string'), new First($stream), Find::class];
        
        yield 'FilterBy_FilterBy' => [
            new FilterBy('a', 'is_int'),
            new FilterBy('b', 'is_string'),
            FilterByMany::class
        ];
        
        yield 'Flip_Collect' => [new Flip(), Collect::create($stream), CollectKeys::class];
        yield 'Flip_CollectKeys' => [new Flip(), new CollectKeys($stream), Collect::class];
        
        yield 'Gather_Flat_1' => [Gather::create(), new Flat(), Flat::class];
        yield 'Gather_Flat_2' => [Gather::create(true), new Flat(), Reindex::class, Flat::class];
        yield 'Gather_Flat_3' => [Gather::create(), new Flat(1)];
        yield 'Gather_Flat_4' => [Gather::create(true), new Flat(1), Reindex::class];
        yield 'Gather_Flat_5' => [Gather::create(true), new Flat(2), Reindex::class, Flat::class];
        yield 'Gather_Reindex_custom' => [Gather::create(), new Reindex(1), Gather::class, Reindex::class];
        yield 'Gather_Reindex_default' => [Gather::create(), new Reindex(), Gather::class];
        
        yield 'Limit_equal' => [new Limit(5), Limit::class];
        yield 'Limit_greater' => [new Limit(7), new Tail(5), Limit::class, Skip::class];
        yield 'Limit_one_Shuffle' => [new Limit(1), Shuffle::create(), Limit::class];
        yield 'Limit_First' => [new Limit(5), new First($stream), First::class];
        
        yield 'MakeTuple_UnpackTuple' => [Tuple::create(), UnpackTuple::create()];
        
        yield 'MapFieldWhen_normal' => [new MapFieldWhen('foo', 'is_string', $mapper), MapFieldWhen::class];
        yield 'MapFieldWhen_barren' => [new MapFieldWhen('foo', 'is_string', $mapper, $mapper), Map::class];
        
        yield 'MapKey_Unpacktuple' => [new MapKey(1), UnpackTuple::create(), UnpackTuple::class];
        
        yield 'MapToBool_MapToBool' => [new Map(Mappers::toBool()), new Map(Mappers::toBool()), Map::class];
        yield 'MapToBool_MapToInt' => [new Map(Mappers::toBool()), new Map(Mappers::toInt()), MapMany::class];
        
        yield 'MapTokenize_Flat' => [new Map(Mappers::tokenize()), new Flat(), Tokenize::class];
        
        yield 'MapWhen_normal_1' => [new MapWhen('is_string', 'strtoupper'), MapWhen::class];
        yield 'MapWhen_normal_2' => [new MapWhen('is_string', 'strtoupper', Mappers::value()), MapWhen::class];
        yield 'MapWhen_normal_3' => [new MapWhen('is_string', Mappers::shuffle(), Mappers::reverse()), MapWhen::class];
        yield 'MapWhen_barren_1' => [new MapWhen('is_string', Mappers::value())];
        yield 'MapWhen_barren_2' => [new MapWhen('is_string', Mappers::value(), Mappers::value())];
        yield 'MapWhen_simple_1' => [new MapWhen('is_string', $mapper, $mapper), Map::class];
        yield 'MapWhen_simple_2' => [new MapWhen('is_string', Mappers::shuffle(), Mappers::shuffle()), Map::class];
        
        yield 'MapWhen_ToInt_ToInt' => [new MapWhen('is_int', Mappers::toInt(), Mappers::toInt()), Map::class];
        yield 'MapWhen_ToInt_ToFloat' => [new MapWhen('is_int', Mappers::toInt(), Mappers::toFloat()), MapWhen::class];
        
        yield 'MapWhen_ToInt_two_different_fields' => [
            new MapWhen('is_int', Mappers::toInt('id'), Mappers::toInt('age')), MapWhen::class
        ];
        
        yield 'MapWhen_ToInt_two_the_same_fields' => [
            new MapWhen('is_int', Mappers::toInt('id'), Mappers::toInt('id')), Map::class
        ];
        
        yield 'MapWhen_ToInt_simple_and_field' => [
            new MapWhen('is_int', Mappers::toInt(), Mappers::toInt('id')), MapWhen::class
        ];
        
        yield 'MapWhen_ToInt_ToFloat_the_same_fields' => [
            new MapWhen('is_int', Mappers::toInt('id'), Mappers::toFloat('id')), MapWhen::class
        ];
        
        yield 'Reindex_Accumulate_1' => [
            new Reindex(), Accumulate::create('is_string'), Reindex::class, Accumulate::class
        ];
        yield 'Reindex_Accumulate_2' => [
            new Reindex(), Accumulate::create('is_string', Check::VALUE, true), Accumulate::class
        ];
        yield 'Reindex_Accumulate_3' => [
            new Reindex(1, 2), Accumulate::create('is_string'), Reindex::class, Accumulate::class
        ];
        yield 'Reindex_Accumulate_4' => [
            new Reindex(1, 2), Accumulate::create('is_string', Check::VALUE, true), Accumulate::class
        ];
        yield 'Reindex_Chunk_1' => [new Reindex(), Chunk::create(3), Reindex::class, Chunk::class];
        yield 'Reindex_Chunk_2' => [new Reindex(), Chunk::create(3, true), Chunk::class];
        yield 'Reindex_Chunk_3' => [new Reindex(1, 2), Chunk::create(3), Reindex::class, Chunk::class];
        yield 'Reindex_Chunk_4' => [new Reindex(1, 2), Chunk::create(3, true), Chunk::class];
        yield 'Reindex_ChunkBy_1' => [new Reindex(), ChunkBy::create($discriminator), Reindex::class, ChunkBy::class];
        yield 'Reindex_ChunkBy_2' => [new Reindex(), ChunkBy::create($discriminator, true), ChunkBy::class];
        yield 'Reindex_ChunkBy_3' => [new Reindex(1, 2), ChunkBy::create($discriminator), Reindex::class, ChunkBy::class];
        yield 'Reindex_ChunkBy_4' => [new Reindex(1, 2), ChunkBy::create($discriminator, true), ChunkBy::class];
        yield 'Reindex_Collect_1' => [new Reindex(), Collect::create($stream), Reindex::class, Collect::class];
        yield 'Reindex_Collect_2' => [new Reindex(), Collect::create($stream, true), Collect::class];
        yield 'Reindex_Collect_3' => [new Reindex(1, 2), Collect::create($stream), Reindex::class, Collect::class];
        yield 'Reindex_Collect_4' => [new Reindex(1, 2), Collect::create($stream, true), Collect::class];
        yield 'Reindex_Count' => [new Reindex(), new Count($stream), Count::class];
        yield 'Reindex_Gather_1' => [new Reindex(), Gather::create(), Gather::class];
        yield 'Reindex_Gather_2' => [new Reindex(1), Gather::create(), Reindex::class, Gather::class];
        yield 'Reindex_Gather_3' => [new Reindex(0, 2), Gather::create(), Reindex::class, Gather::class];
        yield 'Reindex_Gather_4' => [new Reindex(), Gather::create(true), Gather::class];
        yield 'Reindex_Gather_5' => [new Reindex(1), Gather::create(true), Gather::class];
        yield 'Reindex_Gather_6' => [new Reindex(0, 2), Gather::create(true), Gather::class];
        yield 'Reindex_Segregate_1' => [new Reindex(), new Segregate(), Reindex::class, Segregate::class];
        yield 'Reindex_Segregate_2' => [new Reindex(), new Segregate(null, true, Compare::values()), Segregate::class];
        yield 'Reindex_Segregate_3' => [new Reindex(1, 2), new Segregate(), Reindex::class, Segregate::class];
        yield 'Reindex_Segregate_4' => [
            new Reindex(1, 2), new Segregate(null, true, Compare::values()), Segregate::class
        ];
        yield 'Reindex_UnpackTuple' => [new Reindex(), UnpackTuple::create(), UnpackTuple::class];
        yield 'Reindex_Uptrends_1' => [new Reindex(), Uptrends::create(), Reindex::class, Uptrends::class];
        yield 'Reindex_Uptrends_2' => [new Reindex(), Uptrends::create(true), Uptrends::class];
        yield 'Reindex_Uptrends_3' => [new Reindex(1, 2), Uptrends::create(), Reindex::class, Uptrends::class];
        yield 'Reindex_Uptrends_4' => [
            new Reindex(1, 2), Uptrends::create(true), Uptrends::class
        ];
        
        yield 'Reverse_Count' => [new Reverse(), new Count($stream), Count::class];
        yield 'Reverse_Find' => [new Reverse(), new Find($stream, 'foo'), Find::class];
        yield 'Reverse_First' => [new Reverse(), new First($stream), Last::class];
        yield 'Reverse_Has' => [new Reverse(), new Has($stream, 'foo'), Has::class];
        yield 'Reverse_HasEvery' => [new Reverse(), HasEvery::create($stream, ['foo']), HasEvery::class];
        yield 'Reverse_HasOnly' => [new Reverse(), HasOnly::create($stream, ['foo']), HasOnly::class];
        yield 'Reverse_Last' => [new Reverse(), new Last($stream), First::class];
        yield 'Reverse_Shuffle' => [new Reverse(), Shuffle::create(), Shuffle::class];
        yield 'Reverse_Shuffle_chunked' => [new Reverse(), Shuffle::create(3), Reverse::class, Shuffle::class];
        yield 'Reverse_Sort' => [new Reverse(), new Sort(), Sort::class];
        yield 'Reverse_SortLimited' => [new Reverse(), SortLimited::create(3), SortLimited::class];
        yield 'Reverse_Tail' => [new Reverse(), new Tail(6), Limit::class, Reverse::class];
        
        yield 'Segregate_equal' => [new Segregate(5), new Tail(5), Segregate::class];
        yield 'Segregate_greater' => [new Segregate(7), new Tail(5), Segregate::class, Skip::class];
        yield 'Segregate_one_Shuffle' => [new Segregate(1), Shuffle::create(), Segregate::class];
        yield 'Segregate_Reindex_custom' => [new Segregate(2), new Reindex(1), Segregate::class, Reindex::class];
        yield 'Segregate_Reindex_default' => [new Segregate(2), new Reindex(), Segregate::class];
        
        yield 'SendTo_SendTo' => [new SendTo(Consumers::counter()), new SendTo(Consumers::counter()), SendToMany::class];
        yield 'SendTo_SendToMany' => [
            new SendTo(Consumers::counter()), new SendToMany(Consumers::counter()), SendToMany::class
        ];
        yield 'SendToMany_SendTo' => [
            new SendToMany(Consumers::counter()), new SendTo(Consumers::counter()), SendToMany::class
        ];
        yield 'SendToMany_SendToMany' => [
            new SendToMany(Consumers::counter()), new SendToMany(Consumers::counter()), SendToMany::class
        ];
        
        yield 'Shuffle_Count' => [Shuffle::create(), new Count($stream), Count::class];
        yield 'Shuffle_Find' => [Shuffle::create(), new Find($stream, 'foo'), Find::class];
        yield 'Shuffle_Has' => [Shuffle::create(), new Has($stream, 'foo'), Has::class];
        yield 'Shuffle_HasEvery' => [Shuffle::create(), HasEvery::create($stream, ['foo']), HasEvery::class];
        yield 'Shuffle_HasOnly' => [Shuffle::create(), HasOnly::create($stream, ['foo']), HasOnly::class];
        yield 'Shuffle_Reverse' => [Shuffle::create(), new Reverse(), Shuffle::class];
        yield 'Shuffle_Shuffle' => [Shuffle::create(), Shuffle::create(), Shuffle::class];
        yield 'Shuffle_Sort' => [Shuffle::create(), new Sort(), Sort::class];
        yield 'Shuffle_SortLimited' => [Shuffle::create(), SortLimited::create(3), SortLimited::class];
        
        yield 'Sort_Count' => [new Sort(), new Count($stream), Count::class];
        yield 'Sort_Find' => [new Sort(), new Find($stream, 'foo'), Find::class];
        yield 'Sort_First' => [new Sort(), new First($stream), SortLimited::class, First::class];
        yield 'Sort_Has' => [new Sort(), new Has($stream, 'foo'), Has::class];
        yield 'Sort_HasEvery' => [new Sort(), HasEvery::create($stream, ['foo']), HasEvery::class];
        yield 'Sort_HasOnly' => [new Sort(), HasOnly::create($stream, ['foo']), HasOnly::class];
        yield 'Sort_Last' => [new Sort(), new Last($stream), SortLimited::class, First::class];
        yield 'Sort_Shuffle' => [new Sort(), Shuffle::create(), Shuffle::class];
        yield 'Sort_Shuffle_chunked' => [new Sort(), Shuffle::create(5), Sort::class, Shuffle::class];
        yield 'Sort_Sort' => [new Sort(), new Sort(), Sort::class];
        yield 'Sort_SortLimited' => [new Sort(), SortLimited::create(15), SortLimited::class];
        yield 'Sort_Tail' => [new Sort(), new Tail(5), SortLimited::class, Reverse::class];
        
        yield 'SortLimited_equal' => [SortLimited::create(5), new Tail(5), SortLimited::class];
        yield 'SortLimited_greater' => [SortLimited::create(7), new Tail(5), SortLimited::class, Skip::class];
        yield 'SortLimited_one_Shuffle' => [SortLimited::create(1), Shuffle::create(), SortLimited::class];
        yield 'SortLimited_First' => [
            SortLimited::create(5), new First($stream), SortLimited\SingleSortLimited::class, First::class
        ];
        yield 'SortLimited_Reverse' => [SortLimited::create(1), new Reverse(), SortLimited::class];
        
        yield 'Tail_Last' => [new Tail(3), new Last($stream), Last::class];
        yield 'Tail_Tail' => [new Tail(3), new Tail(2), Tail::class];
        
        yield 'Tokenize_Reindex_custom' => [new Tokenize(' '), new Reindex(1), Tokenize::class, Reindex::class];
        yield 'Tokenize_Reindex_default' => [new Tokenize(' '), new Reindex(), Tokenize::class];
        
        yield 'Tuple_Reindex_custom' => [Tuple::create(), new Reindex(1), Tuple::class, Reindex::class];
        yield 'Tuple_Reindex_default' => [Tuple::create(), new Reindex(), Tuple::class];
        
        yield 'Unique' => [new Unique(), OmitReps::class, Unique::class];
        yield 'Unique_FilterBy' => [
            new Unique(), new FilterBy('a', 'is_int'), new FilterBy('b', 'is_string'),
            FilterByMany::class, OmitReps::class, Unique::class
        ];
        
        yield 'UnpackTuple_MakeTuple' => [UnpackTuple::create(), Tuple::create()];
        
        yield 'Window_normal' => [new Window(2), Window::class];
        yield 'Window_reindex' => [new Window(2, 1, true), Window::class];
        yield 'Window_as_Chunk' => [new Window(1, 1), Chunk::class];
        yield 'Window_Flat_1_normal' => [new Window(1, 1), new Flat(1)];
        yield 'Window_Flat_1_reindex' => [new Window(1, 1, true), new Flat(1), Chunk::class, Flat::class];
        yield 'Window_Flat_2_normal' => [new Window(1, 1), new Flat(), Flat::class];
        yield 'Window_Flat_2_reindex' => [new Window(1, 1, true), new Flat(), Chunk::class, Flat::class];
        yield 'Window_Flat_3_normal' => [new Window(2, 1), new Flat(), Window::class, Flat::class];
        yield 'Window_Flat_3_reindex' => [new Window(2, 1, true), new Flat(), Window::class, Flat::class];
        yield 'Window_Flat_4_normal' => [new Window(2, 1), new Flat(1), Window::class, Flat::class];
        yield 'Window_Flat_4_reindex' => [new Window(2, 1, true), new Flat(1), Window::class, Flat::class];
        
        //special cases
        
        yield 'Reverse_Unique_Shuffle_Filter' => [
            new Reverse(), new Unique(), Shuffle::create(), new Filter('is_string'),
            FilterMany::class, Reverse::class, OmitReps::class, Unique::class, Shuffle::class
        ];
        
        yield 'IsEmpty' => [
            new Segregate(3), Categorize::create(Discriminators::byKey()), new Classify(Discriminators::byKey()),
            Chunk::create(3), ChunkBy::create(Discriminators::byField('foo')), new Flip(),
            new MapFieldWhen('foo', 'is_string', Mappers::shuffle()), new Flat(), new OmitReps(),
            MapKeyValue::create(static fn($v, $k): array => [$k => $v]), Gather::create(), new MapKey(Mappers::value()),
            new Map('strtolower'), new Reindex(), new Reverse(), new Scan(0, Reducers::sum()), Shuffle::create(),
            Tuple::create(), new Tail(4), new Unique(), new Sort(), SortLimited::create(5), new IsEmpty($stream, true),
            IsEmpty::class
        ];
    }
    
    /**
     * @dataProvider getDataForTestChainFlatFlat
     */
    public function test_chain_Flat_Flat(int $firstLevel, int $secondLevel, int $expected): void
    {
        //given
        $stream = Stream::empty();
        $pipe = $this->getPipeFromStream($stream);
        
        //when
        $flat = new Flat($firstLevel);
        $this->chainOperations($pipe, $stream, $flat, new Flat($secondLevel));
        
        //then
        $this->assertPipeContainsOperations($pipe, Flat::class);
        
        self::assertSame($expected, $flat->maxLevel());
    }
    
    public static function getDataForTestChainFlatFlat(): array
    {
        return [
            //firstLevel, secondLevel, expectedMaxLevel
            [0, 0, Flattener::MAX_LEVEL],
            [0, 1, Flattener::MAX_LEVEL],
            [1, 0, Flattener::MAX_LEVEL],
            [1, 1, 2],
        ];
    }
    
    /**
     * @dataProvider getDataForTestChainShuffleShuffle
     */
    public function test_chain_Shuffle_Shuffle(?int $firstChunkSize, ?int $secondChunkSize, bool $isChunked): void
    {
        //given
        $stream = Stream::empty();
        $pipe = $this->getPipeFromStream($stream);
        
        //when
        $shuffle = Shuffle::create($firstChunkSize);
        $this->chainOperations($pipe, $stream, $shuffle, Shuffle::create($secondChunkSize));
        
        //then
        if ($isChunked) {
            $this->assertPipeContainsOperations($pipe, ShuffleChunks::class);
        } else {
            $this->assertPipeContainsOperations($pipe, ShuffleAll::class);
        }
    }
    
    public static function getDataForTestChainShuffleShuffle(): array
    {
        return [
            [null, null, false],
            [3, null, false],
            [null, 3, false],
            [5, 3, true],
        ];
    }
    
    public function test_chain_Segregate_First(): void
    {
        //given
        $stream = Stream::empty();
        $pipe = $this->getPipeFromStream($stream);
        $operation = new Segregate(5);
        
        //when
        $this->chainOperations($pipe, $stream, $operation, new First($stream));
        
        //then
        self::assertSame(1, $operation->limit());
    }
    
    public function test_stacked_two_everyNth(): void
    {
        //given
        [$stream, $pipe, ] = $this->prepare();

        $everyNth = new EveryNth(2);

        //when
        $this->chainOperations($pipe, $stream, $everyNth, new EveryNth(3));

        //then
        $this->assertPipeContainsOperations($pipe, EveryNth::class);
        self::assertSame(6, $everyNth->num());
    }
    
    public function test_stacked_sort_with_last(): void
    {
        //given
        [$stream, $pipe, $signal] = $this->prepare();

        $last = new Last($stream);
        $this->addOperations($pipe, new Sort(), $last);

        //when
        $this->sendToPipe([6, 2, 3, 8, 1, 9], $pipe, $signal);

        //then
        self::assertSame(9, $last->get());
    }
    
    public function test_stacked_tail_with_last(): void
    {
        //given
        [$stream, $pipe, $signal] = $this->prepare();

        $last = new Last($stream);
        $this->addOperations($pipe, new Tail(4), $last);

        //when
        $this->sendToPipe([6, 2, 3, 8, 1, 9], $pipe, $signal);

        //then
        self::assertSame(9, $last->get());
    }
    
    public function test_stacked_gather_with_last(): void
    {
        //given
        [$stream, $pipe, $signal] = $this->prepare();
        
        $last = new Last($stream);
        $this->addOperations($pipe, Gather::create(), $last);
        
        //when
        $this->sendToPipe([6, 2], $pipe, $signal);
        
        //then
        self::assertSame([6, 2], $last->get());
    }
    
    /**
     * @dataProvider getDataForTestStackedIsEmpty
     */
    public function test_stacked_isEmpty(string $operation): void
    {
        //given
        [$stream, $pipe, $signal] = $this->prepare();

        $isEmpty = new IsEmpty($stream, true);
        $this->addOperations($pipe, $this->createOperation($operation), $isEmpty);

        //when
        $this->sendToPipe([6, 2, 3, 8], $pipe, $signal);

        //then
        self::assertFalse($isEmpty->get());
    }
    
    public static function getDataForTestStackedIsEmpty(): array
    {
        return [
            ['gather'],
            ['sort'],
            ['reverse'],
        ];
    }
    
    /**
     * @dataProvider createAllOperationModeVariations
     */
    public function test_stacked_hasOnly_result_false(int $mode, string $operation): void
    {
        //given
        [$stream, $pipe, $signal] = $this->prepare();
        
        $hasOnly = HasOnly::create($stream, [2, 3], $mode);
        $this->addOperations($pipe, $this->createOperation($operation), $hasOnly);
        
        //when
        $this->sendToPipe([6, 2, 3, 8], $pipe, $signal);
        
        //then
        self::assertFalse($hasOnly->get());
    }
    
    public function test_sort_hasOnly_check_both_result_true(): void
    {
        //given
        [$stream, $pipe, $signal] = $this->prepare();
        
        $hasOnly = HasOnly::create($stream, [0, 1], Check::BOTH);
        $this->addOperations($pipe, $this->createOperation('sort'), $hasOnly);
        
        //when
        $this->sendToPipe([0, 1], $pipe, $signal);
        
        //then
        self::assertTrue($hasOnly->get());
    }
    
    /**
     * @dataProvider createAllOperationModeVariations
     */
    public function test_stacked_hasEvery(int $mode, string $operation): void
    {
        //given
        [$stream, $pipe, $signal] = $this->prepare();
        
        $hasEvery = HasEvery::create($stream, [2, 4], $mode);
        $this->addOperations($pipe, $this->createOperation($operation), $hasEvery);
        
        //when
        $this->sendToPipe([6, 2, 3, 8], $pipe, $signal);
        
        //then
        self::assertFalse($hasEvery->get());
    }
    
    /**
     * @dataProvider createAllOperationModeVariations
     */
    public function test_stacked_has(int $mode, string $operation): void
    {
        //given
        [$stream, $pipe, $signal] = $this->prepare();

        $hasEvery = HasEvery::create($stream, [2, 4], $mode);
        $this->addOperations($pipe, $this->createOperation($operation), $hasEvery);

        //when
        $this->sendToPipe([6, 2, 3, 8], $pipe, $signal);

        //then
        self::assertFalse($hasEvery->get());
    }
    
    public static function createAllOperationModeVariations(): \Generator
    {
        foreach (['gather', 'sort', 'reverse'] as $operation) {
            foreach ([Check::VALUE, Check::KEY, Check::ANY, Check::BOTH] as $mode) {
                yield [$mode, $operation];
            }
        }
    }
    
    public function test_pipe(): void
    {
        //given
        $stream = Stream::empty();
        $collector = Collectors::values();
        $signal = new Signal($stream);
        
        $this->initializeStream($stream);
        
        $pipe = new Pipe();
        $pipe->chainOperation(new Filter(Filters::greaterThan(5)), $stream);
        $pipe->chainOperation(CollectIn::create($collector), $stream);
        $pipe->chainOperation(new Limit(5), $stream);
        $pipe->prepare();
        
        //when
        foreach ([2, 8, 4, 1, 5, 2, 6, 9, 2, 0, 7, 3, 6, 4, 8, 2, 9, 2, 8] as $key => $value) {
            $signal->item->key = $key;
            $signal->item->value = $value;
            
            $pipe->head->handle($signal);
            
            if ($signal->isEmpty) {
                break;
            }
        }
        
        //then
        self::assertSame([8, 6, 9, 7, 6], $collector->toArray());
    }
    
    public function test_pipe_cannot_be_cloned_when_its_stack_is_not_empty(): void
    {
        //Assert
        $this->expectExceptionObject(PipeExceptionFactory::cannotClonePipeWithNoneEmptyStack());
        
        //Arrange
        $pipe = new Pipe();
        $pipe->stack[] = new Limit(1);
        
        //Act
        $method = (new \ReflectionObject($pipe))->getMethod('__clone');
        $method->setAccessible(true);
        $method->invoke($pipe);
    }
    
    public function test_pipe_forget(): void
    {
        //given
        [, $pipe, $signal] = $this->prepare();
        
        $sort = new Sort();
        $accumulate = Accumulate::create('is_string');
        $filter = new Filter('is_int');
        
        $this->addOperations($pipe, $sort, $accumulate, $filter);
        $pipe->prepare();
        
        self::assertInstanceOf(Sort::class, $pipe->head);
        $this->assertPipeContainsOperations($pipe, Sort::class, Accumulate::class, Filter::class);
        
        $signal->forget($accumulate);
        self::assertInstanceOf(Sort::class, $pipe->head);
        $this->assertPipeContainsOperations($pipe, Sort::class, Filter::class);
        
        //repeat to check if nothing wrong happend
        $signal->forget($accumulate);
        self::assertInstanceOf(Sort::class, $pipe->head);
        $this->assertPipeContainsOperations($pipe, Sort::class, Filter::class);
        
        $signal->forget($sort);
        $this->assertPipeContainsOperations($pipe, Filter::class);
        self::assertInstanceOf(Filter::class, $pipe->head);
        
        $signal->forget($filter);
        self::assertInstanceOf(Ending::class, $pipe->head);
    }
    
    public function test_pipe_forget_with_operation_put_in_stack(): void
    {
        //given
        [, $pipe, $signal] = $this->prepare();
        
        $filter = new Filter('is_int');
        $sort = new Sort();
        $map = new Map(Mappers::trim());
        
        $this->addOperations($pipe, $filter, $sort, $map);
        $pipe->prepare();
        
        $signal->continueWith(Producers::from([3, 4]), $sort);
        self::assertSame([$filter], $pipe->stack);
        
        //when
        $signal->forget($filter);
        
        //then
        self::assertSame([], $pipe->stack);
        $this->assertPipeContainsOperations($pipe, Sort::class, Map::class);
    }
    
    public function test_SendToMany_in_one_call_method(): void
    {
        //given
        [$stream, $pipe] = $this->prepare();
        
        //when
        $stream->call(Consumers::counter(), Consumers::counter());
        
        //then
        $this->assertPipeContainsOperations($pipe, SendToMany::class);
    }
    
    public function test_Until_with_FilterNOT(): void
    {
        //given
        [$stream, $pipe] = $this->prepare();
        
        $operation = new Until(Filters::NOT('is_string'));
        self::assertTrue($operation->shouldBeInversed());
        
        //when
        $this->chainOperations($pipe, $stream, $operation);
        
        //then
        $addedOperation = $pipe->head->getNext();
        
        self::assertNotSame($addedOperation, $operation);
        self::assertInstanceOf(Until::class, $addedOperation);
        self::assertFalse($addedOperation->shouldBeInversed());
    }
    
    public function test_Until_throws_exception_when_cannot_be_inversed(): void
    {
        //Assert
        $this->expectExceptionObject(OperationExceptionFactory::cannotInverseOperation());
        
        //Arrange
        $operation = new Until('is_string');
        self::assertFalse($operation->shouldBeInversed());
        
        //Act
        $operation->createInversed();
    }
    
    public function test_SkipWhile_with_FilterNOT(): void
    {
        //given
        [$stream, $pipe] = $this->prepare();
        
        $operation = new SkipWhile(Filters::NOT('is_string'));
        self::assertTrue($operation->shouldBeInversed());
        
        //when
        $this->chainOperations($pipe, $stream, $operation);
        
        //then
        $addedOperation = $pipe->head->getNext();
        
        self::assertNotSame($addedOperation, $operation);
        self::assertInstanceOf(SkipWhile::class, $addedOperation);
        self::assertFalse($addedOperation->shouldBeInversed());
    }
    
    public function test_SkipWhile_throws_exception_when_cannot_be_inversed(): void
    {
        //Assert
        $this->expectExceptionObject(OperationExceptionFactory::cannotInverseOperation());
        
        //Arrange
        $operation = new SkipWhile('is_string');
        self::assertFalse($operation->shouldBeInversed());
        
        //Act
        $operation->createInversed();
    }
    
    private function createOperation(string $name): Operation
    {
        switch ($name) {
            case 'gather': return Gather::create();
            case 'sort': return new Sort();
            case 'reverse': return new Reverse();
            default:
                throw new \InvalidArgumentException('Cannot create operation '.$name);
        }
    }
    
    private function sendToPipe(array $data, Pipe $pipe, Signal $signal): void
    {
        $pipe->prepare();
        
        $item = $signal->item;
        foreach ($data as $item->key => $item->value) {
            $pipe->head->handle($signal);
            
            if ($signal->isEmpty) {
                return;
            }
        }
        
        $signal->streamIsEmpty();
        $pipe->head->streamingFinished($signal);
    }
    
    private function assertPipeContainsOperations(Pipe $pipe, string ...$classes): void
    {
        $next = $pipe->head;
        if ($next instanceof Initial) {
            $next = $next->getNext();
        }
        
        foreach ($classes as $expectedClass) {
            self::assertInstanceOf($expectedClass, $next);
            $next = $next->getNext();
        }
        
        self::assertInstanceOf(Ending::class, $next);
    }
    
    private function chainOperations(Pipe $pipe, Stream $stream, Operation ...$operations): void
    {
        foreach ($operations as $operation) {
            $pipe->chainOperation($operation, $stream);
        }
    }
    
    private function addOperations(Pipe $pipe, Operation ...$operations): void
    {
        foreach ($operations as $operation) {
            $pipe->append($operation);
        }
    }
    
    /**
     * @return array{Stream, Pipe, Signal}
     */
    private function prepare(): array
    {
        $stream = Stream::empty();
        $pipe = $this->getPipeFromStream($stream);
        $signal = $this->getSignalFromStream($stream);
        
        return [$stream, $pipe, $signal];
    }
    
    private function getPipeFromStream(Stream $stream): Pipe
    {
        return $this->getPropertyFromStream($stream, 'pipe');
    }
    
    private function getSignalFromStream(Stream $stream): Signal
    {
        return $this->getPropertyFromStream($stream, 'signal');
    }
    
    private function getPropertyFromStream(Stream $stream, string $property)
    {
        $this->initializeStream($stream);
        
        $prop = (new \ReflectionObject($stream))->getProperty($property);
        $prop->setAccessible(true);
        
        return $prop->getValue($stream);
    }
    
    private function initializeStream(Stream $stream): void
    {
        $method = (new \ReflectionObject($stream))->getMethod('initialize');
        $method->setAccessible(true);
        $method->invoke($stream);
    }
}