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
use FiiSoft\Jackdaw\Operation\Collecting\{Gather, Reverse, Segregate, ShuffleAll, Sort, SortLimited,
    SortLimited\SingleSortLimited, Tail};
use FiiSoft\Jackdaw\Operation\Exception\OperationExceptionFactory;
use FiiSoft\Jackdaw\Operation\Filtering\{EveryNth, Filter, FilterByMany, FilterMany, OmitReps, Skip, SkipWhile, Unique,
    Uptrends};
use FiiSoft\Jackdaw\Operation\Internal\Operations as OP;
use FiiSoft\Jackdaw\Operation\Internal\Pipe\Ending;
use FiiSoft\Jackdaw\Operation\Internal\Pipe\Initial;
use FiiSoft\Jackdaw\Operation\Internal\Shuffle;
use FiiSoft\Jackdaw\Operation\Mapping\{Accumulate, Aggregate, Chunk, ChunkBy, Flat, Map, MapFieldWhen, MapKey, MapMany,
    MapWhen, Reindex, Tokenize, Tuple, UnpackTuple, Window};
use FiiSoft\Jackdaw\Operation\Operation;
use FiiSoft\Jackdaw\Operation\Sending\{FeedMany, SendToMany};
use FiiSoft\Jackdaw\Operation\Special\{Limit, ReadManyWhile, ReadNext, ShuffleChunks, Until};
use FiiSoft\Jackdaw\Operation\Terminating\{Collect, CollectKeys, Count, Find, First, Has, HasEvery, HasOnly, IsEmpty,
    Last};
use FiiSoft\Jackdaw\Producer\Generator\Flattener;
use FiiSoft\Jackdaw\Producer\Producers;
use FiiSoft\Jackdaw\Reducer\Reducers;
use FiiSoft\Jackdaw\Stream;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PipeTest extends TestCase
{
    /**
     * @dataProvider getDataForTestGeneralChainOperations
     */
    #[DataProvider('getDataForTestGeneralChainOperations')]
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
            OP::accumulate('is_subclass_of'), OP::reindex(1), Accumulate::class, Reindex::class
        ];
        yield 'Accumulate_Reindex_default' => [OP::accumulate('is_string'), OP::reindex(), Accumulate::class];

        yield 'Aggregate_Reindex_custom' => [
            OP::aggregate(['a']), OP::reindex(1), Aggregate::class, Reindex::class
        ];
        yield 'Aggregate_Reindex_default' => [OP::aggregate(['a']), OP::reindex(), Aggregate::class];

        yield 'Chunk_Flat_1' => [OP::chunk(1), OP::flat(1)];
        yield 'Chunk_Flat_3' => [OP::chunk(2, true), OP::flat(1), Chunk::class, Flat::class];
        yield 'Chunk_Flat_2' => [OP::chunk(3), OP::flat(), Flat::class];
        yield 'Chunk_Flat_4' => [OP::chunk(4, true), OP::flat(), Chunk::class, Flat::class];
        yield 'Chunk_Reindex_custom' => [OP::chunk(2), OP::reindex(1), Chunk::class, Reindex::class];
        yield 'Chunk_Reindex_default' => [OP::chunk(2), OP::reindex(), Chunk::class];

        yield 'EveryNth_1' => [OP::everyNth(1)];
        yield 'EveryNth_2' => [OP::everyNth(2), EveryNth::class];
        yield 'EveryNth_EveryNth' => [OP::everyNth(2), OP::everyNth(3), EveryNth::class];

        yield 'Feed_Feed' => [OP::feed($stream), OP::feed($stream), FeedMany::class];

        yield 'Filter_Filter_Unique_Filter' => [
            OP::filter('is_string'), OP::filter('is_string'), OP::unique(), OP::filter('is_string'),
            FilterMany::class, OmitReps::class, Unique::class
        ];
        
        yield 'Filter_First' => [OP::filter('is_string'), OP::first($stream), Find::class];

        yield 'FilterBy_FilterBy' => [
            OP::filterBy('a', 'is_int'),
            OP::filterBy('b', 'is_string'),
            FilterByMany::class
        ];

        yield 'Flip_Collect' => [OP::flip(), OP::collect($stream), CollectKeys::class];
        yield 'Flip_CollectKeys' => [OP::flip(), OP::collectKeys($stream), Collect::class];

        yield 'Gather_Flat_1' => [OP::gather(), OP::flat(), Flat::class];
        yield 'Gather_Flat_2' => [OP::gather(true), OP::flat(), Reindex::class, Flat::class];
        yield 'Gather_Flat_3' => [OP::gather(), OP::flat(1)];
        yield 'Gather_Flat_4' => [OP::gather(true), OP::flat(1), Reindex::class];
        yield 'Gather_Flat_5' => [OP::gather(true), OP::flat(2), Reindex::class, Flat::class];
        yield 'Gather_Reindex_custom' => [OP::gather(), OP::reindex(1), Gather::class, Reindex::class];
        yield 'Gather_Reindex_default' => [OP::gather(), OP::reindex(), Gather::class];

        yield 'Limit_equal' => [OP::limit(5), Limit::class];
        yield 'Limit_greater' => [OP::limit(7), OP::tail(5), Limit::class, Skip::class];
        yield 'Limit_one_Shuffle' => [OP::limit(1), OP::shuffle(), Limit::class];
        yield 'Limit_First' => [OP::limit(5), OP::first($stream), First::class];

        yield 'MakeTuple_UnpackTuple' => [OP::makeTuple(), OP::unpackTuple()];

        yield 'MapFieldWhen_normal' => [OP::mapFieldWhen('foo', 'is_string', $mapper), MapFieldWhen::class];
        yield 'MapFieldWhen_barren' => [OP::mapFieldWhen('foo', 'is_string', $mapper, $mapper), Map::class];

        yield 'MapKey_Unpacktuple' => [OP::mapKey(1), OP::unpackTuple(), UnpackTuple::class];

        yield 'MapToBool_MapToBool' => [OP::map(Mappers::toBool()), OP::map(Mappers::toBool()), Map::class];
        yield 'MapToBool_MapToInt' => [OP::map(Mappers::toBool()), OP::map(Mappers::toInt()), MapMany::class];

        yield 'MapTokenize_Flat' => [OP::map(Mappers::tokenize()), OP::flat(), Tokenize::class];

        yield 'MapWhen_normal_1' => [OP::mapWhen('is_string', 'strtoupper'), MapWhen::class];
        yield 'MapWhen_normal_2' => [OP::mapWhen('is_string', 'strtoupper', Mappers::value()), MapWhen::class];
        yield 'MapWhen_normal_3' => [OP::mapWhen('is_string', Mappers::shuffle(), Mappers::reverse()), MapWhen::class];
        yield 'MapWhen_barren_1' => [OP::mapWhen('is_string', Mappers::value())];
        yield 'MapWhen_barren_2' => [OP::mapWhen('is_string', Mappers::value(), Mappers::value())];
        yield 'MapWhen_simple_1' => [OP::mapWhen('is_string', $mapper, $mapper), Map::class];
        yield 'MapWhen_simple_2' => [OP::mapWhen('is_string', Mappers::shuffle(), Mappers::shuffle()), Map::class];

        yield 'MapWhen_ToInt_ToInt' => [OP::mapWhen('is_int', Mappers::toInt(), Mappers::toInt()), Map::class];
        yield 'MapWhen_ToInt_ToFloat' => [OP::mapWhen('is_int', Mappers::toInt(), Mappers::toFloat()), MapWhen::class];

        yield 'MapWhen_ToInt_two_different_fields' => [
            OP::mapWhen('is_int', Mappers::toInt('id'), Mappers::toInt('age')), MapWhen::class
        ];

        yield 'MapWhen_ToInt_two_the_same_fields' => [
            OP::mapWhen('is_int', Mappers::toInt('id'), Mappers::toInt('id')), Map::class
        ];

        yield 'MapWhen_ToInt_simple_and_field' => [
            OP::mapWhen('is_int', Mappers::toInt(), Mappers::toInt('id')), MapWhen::class
        ];

        yield 'MapWhen_ToInt_ToFloat_the_same_fields' => [
            OP::mapWhen('is_int', Mappers::toInt('id'), Mappers::toFloat('id')), MapWhen::class
        ];
        
        yield 'ReadNext_as_first_operation' => [OP::readNext(), Skip::class, EveryNth::class];
        
        yield 'ReadNext_stacked_as_first_operation' => [
            OP::readNext(), OP::readNext(), Skip::class, EveryNth::class
        ];
        
        yield 'ReadNext_stacked_not_first' => [
            OP::map('strtoupper'), OP::readNext(), OP::readNext(), OP::readNext(),
            Map::class, ReadNext::class
        ];
        
        yield 'ReadNext_with_constant_zero' => [OP::filter('is_string'), OP::readNext(0), Filter::class];
        
        yield 'ReadMany_as_ReadNext_first_operation' => [OP::readMany(1), Skip::class, EveryNth::class];
        
        yield 'ReadMany_as_ReadNext_inner_operation_keep_keys' => [
            OP::map('intval'), OP::readMany(1), Map::class, ReadNext::class
        ];
        
        yield 'ReadMany_as_ReadNext_inner_operation_reindex_keys' => [
            OP::map('intval'), OP::readMany(1, true), Map::class, ReadNext::class, MapKey::class
        ];
        
        yield 'ReadMany_as_first_operation' => [OP::readMany(2), Skip::class, Window::class, Flat::class];
        
        yield 'ReadMany_with_constant_zero' => [OP::filter('is_string'), OP::readMany(0), Filter::class];
        
        yield 'ReadWhile_as_first_operation' => [OP::readWhile('is_string'), Skip::class, Filter::class];
        
        yield 'ReadWhile_as_first_operation_reindex_keys' => [
            OP::readWhile('is_string', null, true), ReadManyWhile::class
        ];
        
        yield 'ReadUntil_as_first_operation' => [OP::readUntil('is_string'), Skip::class, Filter::class];
        
        yield 'ReadUntil_as_first_operation_reindex_keys' => [
            OP::readUntil('is_string', null, true), ReadManyWhile::class
        ];
        
        yield 'Reindex_Accumulate_1' => [
            OP::reindex(), OP::accumulate('is_string'), Reindex::class, Accumulate::class
        ];
        yield 'Reindex_Accumulate_2' => [
            OP::reindex(), OP::accumulate('is_string', true, Check::VALUE), Accumulate::class
        ];
        yield 'Reindex_Accumulate_3' => [
            OP::reindex(1, 2), OP::accumulate('is_string'), Reindex::class, Accumulate::class
        ];
        yield 'Reindex_Accumulate_4' => [
            OP::reindex(1, 2), OP::accumulate('is_string', true, Check::VALUE), Accumulate::class
        ];
        yield 'Reindex_Chunk_1' => [OP::reindex(), OP::chunk(3), Reindex::class, Chunk::class];
        yield 'Reindex_Chunk_2' => [OP::reindex(), OP::chunk(3, true), Chunk::class];
        yield 'Reindex_Chunk_3' => [OP::reindex(1, 2), OP::chunk(3), Reindex::class, Chunk::class];
        yield 'Reindex_Chunk_4' => [OP::reindex(1, 2), OP::chunk(3, true), Chunk::class];
        yield 'Reindex_ChunkBy_1' => [OP::reindex(), OP::chunkBy($discriminator), Reindex::class, ChunkBy::class];
        yield 'Reindex_ChunkBy_2' => [OP::reindex(), OP::chunkBy($discriminator, true), ChunkBy::class];
        yield 'Reindex_ChunkBy_3' => [OP::reindex(1, 2), OP::chunkBy($discriminator), Reindex::class, ChunkBy::class];
        yield 'Reindex_ChunkBy_4' => [OP::reindex(1, 2), OP::chunkBy($discriminator, true), ChunkBy::class];
        yield 'Reindex_Collect_1' => [OP::reindex(), OP::collect($stream), Reindex::class, Collect::class];
        yield 'Reindex_Collect_2' => [OP::reindex(), OP::collect($stream, true), Collect::class];
        yield 'Reindex_Collect_3' => [OP::reindex(1, 2), OP::collect($stream), Reindex::class, Collect::class];
        yield 'Reindex_Collect_4' => [OP::reindex(1, 2), OP::collect($stream, true), Collect::class];
        yield 'Reindex_Count' => [OP::reindex(), OP::count($stream), Count::class];
        yield 'Reindex_Gather_1' => [OP::reindex(), OP::gather(), Gather::class];
        yield 'Reindex_Gather_2' => [OP::reindex(1), OP::gather(), Reindex::class, Gather::class];
        yield 'Reindex_Gather_3' => [OP::reindex(0, 2), OP::gather(), Reindex::class, Gather::class];
        yield 'Reindex_Gather_4' => [OP::reindex(), OP::gather(true), Gather::class];
        yield 'Reindex_Gather_5' => [OP::reindex(1), OP::gather(true), Gather::class];
        yield 'Reindex_Gather_6' => [OP::reindex(0, 2), OP::gather(true), Gather::class];
        yield 'Reindex_Segregate_1' => [OP::reindex(), OP::segregate(), Reindex::class, Segregate::class];
        yield 'Reindex_Segregate_2' => [OP::reindex(), OP::segregate(null, true, Compare::values()), Segregate::class];
        yield 'Reindex_Segregate_3' => [OP::reindex(1, 2), OP::segregate(), Reindex::class, Segregate::class];
        yield 'Reindex_Segregate_4' => [
            OP::reindex(1, 2), OP::segregate(null, true, Compare::values()), Segregate::class
        ];
        yield 'Reindex_UnpackTuple' => [OP::reindex(), OP::unpackTuple(), UnpackTuple::class];
        yield 'Reindex_Uptrends_1' => [OP::reindex(), OP::accumulateUptrends(), Reindex::class, Uptrends::class];
        yield 'Reindex_Uptrends_2' => [OP::reindex(), OP::accumulateUptrends(true), Uptrends::class];
        yield 'Reindex_Uptrends_3' => [OP::reindex(1, 2), OP::accumulateUptrends(), Reindex::class, Uptrends::class];
        yield 'Reindex_Uptrends_4' => [
            OP::reindex(1, 2), OP::accumulateUptrends(true), Uptrends::class
        ];
        
        yield 'Reverse_Count' => [OP::reverse(), OP::count($stream), Count::class];
        yield 'Reverse_Find' => [OP::reverse(), OP::find($stream, 'foo'), Find::class];
        yield 'Reverse_First' => [OP::reverse(), OP::first($stream), Last::class];
        yield 'Reverse_Has' => [OP::reverse(), OP::has($stream, 'foo'), Has::class];
        yield 'Reverse_HasEvery' => [OP::reverse(), OP::hasEvery($stream, ['foo']), HasEvery::class];
        yield 'Reverse_HasOnly' => [OP::reverse(), OP::hasOnly($stream, ['foo']), HasOnly::class];
        yield 'Reverse_Last' => [OP::reverse(), OP::last($stream), First::class];
        yield 'Reverse_Shuffle' => [OP::reverse(), OP::shuffle(), Shuffle::class];
        yield 'Reverse_Shuffle_chunked' => [OP::reverse(), OP::shuffle(3), Reverse::class, Shuffle::class];
        yield 'Reverse_Sort' => [OP::reverse(), OP::sort(), Sort::class];
        yield 'Reverse_SortLimited' => [OP::reverse(), OP::sortLimited(3), SortLimited::class];
        yield 'Reverse_Tail' => [OP::reverse(), OP::tail(6), Limit::class, Reverse::class];
        
        yield 'Segregate_equal' => [OP::segregate(5), OP::tail(5), Segregate::class];
        yield 'Segregate_greater' => [OP::segregate(7), OP::tail(5), Segregate::class, Skip::class];
        yield 'Segregate_one_Shuffle' => [OP::segregate(1), OP::shuffle(), Segregate::class];
        yield 'Segregate_Reindex_custom' => [OP::segregate(2), OP::reindex(1), Segregate::class, Reindex::class];
        yield 'Segregate_Reindex_default' => [OP::segregate(2), OP::reindex(), Segregate::class];
        
        yield 'SendTo_SendTo' => [OP::call(Consumers::counter()), OP::call(Consumers::counter()), SendToMany::class];
        yield 'SendTo_SendToMany' => [
            OP::call(Consumers::counter()), OP::call(Consumers::counter()), SendToMany::class
        ];
        yield 'SendToMany_SendTo' => [
            OP::call(Consumers::counter()), OP::call(Consumers::counter()), SendToMany::class
        ];
        yield 'SendToMany_SendToMany' => [
            OP::call(Consumers::counter()), OP::call(Consumers::counter()), SendToMany::class
        ];
        
        yield 'Shuffle_Count' => [OP::shuffle(), OP::count($stream), Count::class];
        yield 'Shuffle_Find' => [OP::shuffle(), OP::find($stream, 'foo'), Find::class];
        yield 'Shuffle_Has' => [OP::shuffle(), OP::has($stream, 'foo'), Has::class];
        yield 'Shuffle_HasEvery' => [OP::shuffle(), OP::hasEvery($stream, ['foo']), HasEvery::class];
        yield 'Shuffle_HasOnly' => [OP::shuffle(), OP::hasOnly($stream, ['foo']), HasOnly::class];
        yield 'Shuffle_Reverse' => [OP::shuffle(), OP::reverse(), Shuffle::class];
        yield 'Shuffle_Shuffle' => [OP::shuffle(), OP::shuffle(), Shuffle::class];
        yield 'Shuffle_Sort' => [OP::shuffle(), OP::sort(), Sort::class];
        yield 'Shuffle_SortLimited' => [OP::shuffle(), OP::sortLimited(3), SortLimited::class];
        
        yield 'Sort_Count' => [OP::sort(), OP::count($stream), Count::class];
        yield 'Sort_Find' => [OP::sort(), OP::find($stream, 'foo'), Find::class];
        yield 'Sort_First' => [OP::sort(), OP::first($stream), SortLimited::class, First::class];
        yield 'Sort_Has' => [OP::sort(), OP::has($stream, 'foo'), Has::class];
        yield 'Sort_HasEvery' => [OP::sort(), OP::hasEvery($stream, ['foo']), HasEvery::class];
        yield 'Sort_HasOnly' => [OP::sort(), OP::hasOnly($stream, ['foo']), HasOnly::class];
        yield 'Sort_Last' => [OP::sort(), OP::last($stream), SortLimited::class, First::class];
        yield 'Sort_Shuffle' => [OP::sort(), OP::shuffle(), Shuffle::class];
        yield 'Sort_Shuffle_chunked' => [OP::sort(), OP::shuffle(5), Sort::class, Shuffle::class];
        yield 'Sort_Sort' => [OP::sort(), OP::sort(), Sort::class];
        yield 'Sort_SortLimited' => [OP::sort(), OP::sortLimited(15), SortLimited::class];
        yield 'Sort_Tail' => [OP::sort(), OP::tail(5), SortLimited::class, Reverse::class];
        
        yield 'SortLimited_equal' => [OP::sortLimited(5), OP::tail(5), SortLimited::class];
        yield 'SortLimited_greater' => [OP::sortLimited(7), OP::tail(5), SortLimited::class, Skip::class];
        yield 'SortLimited_one_Shuffle' => [OP::sortLimited(1), OP::shuffle(), SortLimited::class];
        yield 'SortLimited_First' => [
            OP::sortLimited(5), OP::first($stream), SingleSortLimited::class, First::class
        ];
        yield 'SortLimited_Reverse' => [OP::sortLimited(1), OP::reverse(), SortLimited::class];
        
        yield 'Tail_Last' => [OP::tail(3), OP::last($stream), Last::class];
        yield 'Tail_Tail' => [OP::tail(3), OP::tail(2), Tail::class];
        
        yield 'Tokenize_Reindex_custom' => [OP::tokenize(), OP::reindex(1), Tokenize::class, Reindex::class];
        yield 'Tokenize_Reindex_default' => [OP::tokenize(), OP::reindex(), Tokenize::class];
        
        yield 'Tuple_Reindex_custom' => [OP::makeTuple(), OP::reindex(1), Tuple::class, Reindex::class];
        yield 'Tuple_Reindex_default' => [OP::makeTuple(), OP::reindex(), Tuple::class];
        
        yield 'Unique' => [OP::unique(), OmitReps::class, Unique::class];
        yield 'Unique_FilterBy' => [
            OP::unique(), OP::filterBy('a', 'is_int'), OP::filterBy('b', 'is_string'),
            FilterByMany::class, OmitReps::class, Unique::class
        ];
        
        yield 'UnpackTuple_MakeTuple' => [OP::unpackTuple(), OP::makeTuple()];
        
        yield 'Window_normal' => [OP::window(2), Window::class];
        yield 'Window_reindex' => [OP::window(2, 1, true), Window::class];
        yield 'Window_as_Chunk' => [OP::window(1, 1), Chunk::class];
        yield 'Window_Flat_1_normal' => [OP::window(1, 1), OP::flat(1)];
        yield 'Window_Flat_1_reindex' => [OP::window(1, 1, true), OP::flat(1), Chunk::class, Flat::class];
        yield 'Window_Flat_2_normal' => [OP::window(1, 1), OP::flat(), Flat::class];
        yield 'Window_Flat_2_reindex' => [OP::window(1, 1, true), OP::flat(), Chunk::class, Flat::class];
        yield 'Window_Flat_3_normal' => [OP::window(2, 1), OP::flat(), Window::class, Flat::class];
        yield 'Window_Flat_3_reindex' => [OP::window(2, 1, true), OP::flat(), Window::class, Flat::class];
        yield 'Window_Flat_4_normal' => [OP::window(2, 1), OP::flat(1), Window::class, Flat::class];
        yield 'Window_Flat_4_reindex' => [OP::window(2, 1, true), OP::flat(1), Window::class, Flat::class];
        
        //special cases
        
        yield 'Reverse_Unique_Shuffle_Filter' => [
            OP::reverse(), OP::unique(), OP::shuffle(), OP::filter('is_string'),
            Filter::class, Reverse::class, OmitReps::class, Unique::class, Shuffle::class
        ];
        
        yield 'Reverse_Unique_Shuffle_Filter_Filter' => [
            OP::reverse(), OP::unique(), OP::shuffle(), OP::filter('is_string'), OP::filter('is_int'),
            FilterMany::class, Reverse::class, OmitReps::class, Unique::class, Shuffle::class
        ];
        
        yield 'IsEmpty' => [
            OP::segregate(3), OP::categorize(Discriminators::byKey()), OP::classify(Discriminators::byKey()),
            OP::chunk(3), OP::chunkBy(Discriminators::byField('foo')), OP::flip(),
            OP::mapFieldWhen('foo', 'is_string', Mappers::shuffle()), OP::flat(), OP::omitReps(),
            OP::mapKeyValue(static fn($v, $k): array => [$k => $v]), OP::gather(), OP::mapKey(Mappers::value()),
            OP::map('strtolower'), OP::reindex(), OP::reverse(), OP::scan(0, Reducers::sum()), OP::shuffle(),
            OP::makeTuple(), OP::tail(4), OP::unique(), OP::sort(), OP::sortLimited(5), OP::isEmpty($stream),
            IsEmpty::class
        ];
    }
    
    /**
     * @dataProvider getDataForTestChainFlatFlat
     */
    #[DataProvider('getDataForTestChainFlatFlat')]
    public function test_chain_Flat_Flat(int $firstLevel, int $secondLevel, int $expected): void
    {
        //given
        $stream = Stream::empty();
        $pipe = $this->getPipeFromStream($stream);
        
        //when
        $flat = OP::flat($firstLevel);
        $this->chainOperations($pipe, $stream, $flat, OP::flat($secondLevel));
        
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
    #[DataProvider('getDataForTestChainShuffleShuffle')]
    public function test_chain_Shuffle_Shuffle(?int $firstChunkSize, ?int $secondChunkSize, bool $isChunked): void
    {
        //given
        $stream = Stream::empty();
        $pipe = $this->getPipeFromStream($stream);
        
        //when
        $shuffle = OP::shuffle($firstChunkSize);
        $this->chainOperations($pipe, $stream, $shuffle, OP::shuffle($secondChunkSize));
        
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
        $operation = OP::segregate(5);
        
        //when
        $this->chainOperations($pipe, $stream, $operation, OP::first($stream));
        
        //then
        self::assertSame(1, $operation->limit());
    }
    
    public function test_stacked_two_everyNth(): void
    {
        //given
        [$stream, $pipe, ] = $this->prepare();

        $everyNth = OP::everyNth(2);

        //when
        $this->chainOperations($pipe, $stream, $everyNth, OP::everyNth(3));

        //then
        $this->assertPipeContainsOperations($pipe, EveryNth::class);
        self::assertSame(6, $everyNth->num());
    }
    
    public function test_stacked_sort_with_last(): void
    {
        //given
        [$stream, $pipe, $signal] = $this->prepare();

        $last = OP::last($stream);
        $this->addOperations($pipe, OP::sort(), $last);

        //when
        $this->sendToPipe([6, 2, 3, 8, 1, 9], $pipe, $signal);

        //then
        self::assertSame(9, $last->get());
    }
    
    public function test_stacked_tail_with_last(): void
    {
        //given
        [$stream, $pipe, $signal] = $this->prepare();

        $last = OP::last($stream);
        $this->addOperations($pipe, OP::tail(4), $last);

        //when
        $this->sendToPipe([6, 2, 3, 8, 1, 9], $pipe, $signal);

        //then
        self::assertSame(9, $last->get());
    }
    
    public function test_stacked_gather_with_last(): void
    {
        //given
        [$stream, $pipe, $signal] = $this->prepare();
        
        $last = OP::last($stream);
        $this->addOperations($pipe, OP::gather(), $last);
        
        //when
        $this->sendToPipe([6, 2], $pipe, $signal);
        
        //then
        self::assertSame([6, 2], $last->get());
    }
    
    /**
     * @dataProvider getDataForTestStackedIsEmpty
     */
    #[DataProvider('getDataForTestStackedIsEmpty')]
    public function test_stacked_isEmpty(string $operation): void
    {
        //given
        [$stream, $pipe, $signal] = $this->prepare();

        $isEmpty = OP::isEmpty($stream);
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
    #[DataProvider('createAllOperationModeVariations')]
    public function test_stacked_hasOnly_result_false(int $mode, string $operation): void
    {
        //given
        [$stream, $pipe, $signal] = $this->prepare();
        
        $hasOnly = OP::hasOnly($stream, [2, 3], $mode);
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
        
        $hasOnly = OP::hasOnly($stream, [0, 1], Check::BOTH);
        $this->addOperations($pipe, $this->createOperation('sort'), $hasOnly);
        
        //when
        $this->sendToPipe([0, 1], $pipe, $signal);
        
        //then
        self::assertTrue($hasOnly->get());
    }
    
    /**
     * @dataProvider createAllOperationModeVariations
     */
    #[DataProvider('createAllOperationModeVariations')]
    public function test_stacked_hasEvery(int $mode, string $operation): void
    {
        //given
        [$stream, $pipe, $signal] = $this->prepare();
        
        $hasEvery = OP::hasEvery($stream, [2, 4], $mode);
        $this->addOperations($pipe, $this->createOperation($operation), $hasEvery);
        
        //when
        $this->sendToPipe([6, 2, 3, 8], $pipe, $signal);
        
        //then
        self::assertFalse($hasEvery->get());
    }
    
    /**
     * @dataProvider createAllOperationModeVariations
     */
    #[DataProvider('createAllOperationModeVariations')]
    public function test_stacked_has(int $mode, string $operation): void
    {
        //given
        [$stream, $pipe, $signal] = $this->prepare();

        $hasEvery = OP::hasEvery($stream, [2, 4], $mode);
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
        
        $pipe = new Pipe($stream);
        $pipe->chainOperation(OP::filter(Filters::greaterThan(5)));
        $pipe->chainOperation(OP::collectIn($collector));
        $pipe->chainOperation(OP::limit(5));
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
        $pipe = new Pipe(Stream::empty());
        $pipe->stack[] = OP::limit(1);
        
        //Act
        $method = (new \ReflectionObject($pipe))->getMethod('__clone');
        $method->setAccessible(true);
        $method->invoke($pipe);
    }
    
    public function test_pipe_forget(): void
    {
        //given
        [, $pipe, $signal] = $this->prepare();
        
        $sort = OP::sort();
        $accumulate = OP::accumulate('is_string');
        $filter = OP::filter('is_int');
        
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
        
        $filter = OP::filter('is_int');
        $sort = OP::sort();
        $map = OP::map(Mappers::trim());
        
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
        
        $operation = OP::until(Filters::NOT('is_string'));
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
        $operation = OP::until('is_string');
        self::assertFalse($operation->shouldBeInversed());
        
        //Act
        $operation->createInversed();
    }
    
    public function test_SkipWhile_with_FilterNOT(): void
    {
        //given
        [$stream, $pipe] = $this->prepare();
        
        $operation = OP::skipWhile(Filters::NOT('is_string'));
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
        $operation = OP::skipWhile('is_string');
        self::assertFalse($operation->shouldBeInversed());
        
        //Act
        $operation->createInversed();
    }
    
    private function createOperation(string $name): Operation
    {
        switch ($name) {
            case 'gather': return OP::gather();
            case 'sort': return OP::sort();
            case 'reverse': return OP::reverse();
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
        $pipe->prepare();
        
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
        $pipe->assignStream($stream);
        
        foreach ($operations as $operation) {
            $pipe->chainOperation($operation);
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
    
    /**
     * @return mixed
     */
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