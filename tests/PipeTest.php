<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Collector\Collectors;
use FiiSoft\Jackdaw\Consumer\Consumers;
use FiiSoft\Jackdaw\Discriminator\Discriminators;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Pipe;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Mapper\Mappers;
use FiiSoft\Jackdaw\Operation\Accumulate;
use FiiSoft\Jackdaw\Operation\Aggregate;
use FiiSoft\Jackdaw\Operation\Categorize;
use FiiSoft\Jackdaw\Operation\Chunk;
use FiiSoft\Jackdaw\Operation\ChunkBy;
use FiiSoft\Jackdaw\Operation\Classify;
use FiiSoft\Jackdaw\Operation\CollectIn;
use FiiSoft\Jackdaw\Operation\Filter;
use FiiSoft\Jackdaw\Operation\FilterMany;
use FiiSoft\Jackdaw\Operation\Flat;
use FiiSoft\Jackdaw\Operation\Flip;
use FiiSoft\Jackdaw\Operation\Gather;
use FiiSoft\Jackdaw\Operation\Internal\Ending;
use FiiSoft\Jackdaw\Operation\Internal\Feed;
use FiiSoft\Jackdaw\Operation\Internal\FeedMany;
use FiiSoft\Jackdaw\Operation\Internal\Initial;
use FiiSoft\Jackdaw\Operation\Internal\Limitable;
use FiiSoft\Jackdaw\Operation\Limit;
use FiiSoft\Jackdaw\Operation\Map;
use FiiSoft\Jackdaw\Operation\MapFieldWhen;
use FiiSoft\Jackdaw\Operation\MapKey;
use FiiSoft\Jackdaw\Operation\MapKeyValue;
use FiiSoft\Jackdaw\Operation\MapWhen;
use FiiSoft\Jackdaw\Operation\OmitReps;
use FiiSoft\Jackdaw\Operation\Operation;
use FiiSoft\Jackdaw\Operation\Reindex;
use FiiSoft\Jackdaw\Operation\Reverse;
use FiiSoft\Jackdaw\Operation\Scan;
use FiiSoft\Jackdaw\Operation\Segregate;
use FiiSoft\Jackdaw\Operation\SendTo;
use FiiSoft\Jackdaw\Operation\Shuffle;
use FiiSoft\Jackdaw\Operation\Skip;
use FiiSoft\Jackdaw\Operation\Sort;
use FiiSoft\Jackdaw\Operation\SortLimited;
use FiiSoft\Jackdaw\Operation\Tail;
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
use FiiSoft\Jackdaw\Operation\Tokenize;
use FiiSoft\Jackdaw\Operation\Tuple;
use FiiSoft\Jackdaw\Operation\Unique;
use FiiSoft\Jackdaw\Operation\UnpackTuple;
use FiiSoft\Jackdaw\Operation\Uptrends;
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
    
    public function getDataForTestGeneralChainOperations(): \Generator
    {
        $stream = Stream::empty();
        $mapper = Mappers::generic('strtolower');
        $discriminator = Discriminators::generic('is_string');
        
        yield 'Accumulate_Reindex_custom' => [
            new Accumulate('is_subclass_of'), new Reindex(1), Accumulate::class, Reindex::class
        ];
        yield 'Accumulate_Reindex_default' => [new Accumulate('is_string'), new Reindex(), Accumulate::class];
        
        yield 'Aggregate_Reindex_custom' => [new Aggregate(['a']), new Reindex(1), Aggregate::class, Reindex::class];
        yield 'Aggregate_Reindex_default' => [new Aggregate(['a']), new Reindex(), Aggregate::class];
        
        yield 'Chunk_Reindex_custom' => [new Chunk(2), new Reindex(1), Chunk::class, Reindex::class];
        yield 'Chunk_Reindex_default' => [new Chunk(2), new Reindex(), Chunk::class];
        
        yield 'Feed_Feed' => [new Feed($stream), new Feed($stream), FeedMany::class];
        
        yield 'Flip_Collect' => [new Flip(), new Collect($stream), CollectKeys::class];
        yield 'Flip_CollectKeys' => [new Flip(), new CollectKeys($stream), Collect::class];
        
        yield 'Gather_Flat_1' => [new Gather(), new Flat(), Flat::class];
        yield 'Gather_Flat_2' => [new Gather(true), new Flat(), Reindex::class, Flat::class];
        yield 'Gather_Flat_3' => [new Gather(), new Flat(1)];
        yield 'Gather_Flat_4' => [new Gather(true), new Flat(1), Reindex::class];
        yield 'Gather_Flat_5' => [new Gather(true), new Flat(2), Reindex::class, Flat::class];
        yield 'Gather_Reindex_custom' => [new Gather(), new Reindex(1), Gather::class, Reindex::class];
        yield 'Gather_Reindex_default' => [new Gather(), new Reindex(), Gather::class];
        
        yield 'Limit_equal' => [new Limit(5), Limit::class];
        yield 'Limit_greater' => [new Limit(7), new Tail(5), Limit::class, Skip::class];
        yield 'Limit_one_Shuffle' => [new Limit(1), new Shuffle(), Limit::class];
        yield 'Limit_First' => [new Limit(5), new First($stream), First::class];
        
        yield 'MakeTuple_UnpackTuple' => [new Tuple(), new UnpackTuple()];
        
        yield 'Map_with_Tokenize_Flat' => [new Map(Mappers::tokenize()), new Flat(), Tokenize::class];
        
        yield 'MapFieldWhen_normal' => [new MapFieldWhen('foo', 'is_string', $mapper), MapFieldWhen::class];
        yield 'MapFieldWhen_barren' => [new MapFieldWhen('foo', 'is_string', $mapper, $mapper), Map::class];
        
        yield 'MapKey_Unpacktuple' => [new MapKey(1), new UnpackTuple(), UnpackTuple::class];
        
        yield 'MapWhen_normal_1' => [new MapWhen('is_string', 'strtoupper'), MapWhen::class];
        yield 'MapWhen_normal_2' => [new MapWhen('is_string', 'strtoupper', Mappers::value()), MapWhen::class];
        yield 'MapWhen_normal_3' => [new MapWhen('is_string', Mappers::shuffle(), Mappers::reverse()), MapWhen::class];
        yield 'MapWhen_barren_1' => [new MapWhen('is_string', Mappers::value())];
        yield 'MapWhen_barren_2' => [new MapWhen('is_string', Mappers::value(), Mappers::value())];
        yield 'MapWhen_simple_1' => [new MapWhen('is_string', $mapper, $mapper), Map::class];
        yield 'MapWhen_simple_2' => [new MapWhen('is_string', Mappers::shuffle(), Mappers::shuffle()), Map::class];
        
        yield 'Reindex_Accumulate_1' => [
            new Reindex(), new Accumulate('is_string'), Reindex::class, Accumulate::class
        ];
        yield 'Reindex_Accumulate_2' => [
            new Reindex(), new Accumulate('is_string', Check::VALUE, true), Accumulate::class
        ];
        yield 'Reindex_Accumulate_3' => [
            new Reindex(1, 2), new Accumulate('is_string'), Reindex::class, Accumulate::class
        ];
        yield 'Reindex_Accumulate_4' => [
            new Reindex(1, 2), new Accumulate('is_string', Check::VALUE, true), Accumulate::class
        ];
        yield 'Reindex_Chunk_1' => [new Reindex(), new Chunk(3), Reindex::class, Chunk::class];
        yield 'Reindex_Chunk_2' => [new Reindex(), new Chunk(3, true), Chunk::class];
        yield 'Reindex_Chunk_3' => [new Reindex(1, 2), new Chunk(3), Reindex::class, Chunk::class];
        yield 'Reindex_Chunk_4' => [new Reindex(1, 2), new Chunk(3, true), Chunk::class];
        yield 'Reindex_ChunkBy_1' => [new Reindex(), new ChunkBy($discriminator), Reindex::class, ChunkBy::class];
        yield 'Reindex_ChunkBy_2' => [new Reindex(), new ChunkBy($discriminator, true), ChunkBy::class];
        yield 'Reindex_ChunkBy_3' => [new Reindex(1, 2), new ChunkBy($discriminator), Reindex::class, ChunkBy::class];
        yield 'Reindex_ChunkBy_4' => [new Reindex(1, 2), new ChunkBy($discriminator, true), ChunkBy::class];
        yield 'Reindex_Collect_1' => [new Reindex(), new Collect($stream), Reindex::class, Collect::class];
        yield 'Reindex_Collect_2' => [new Reindex(), new Collect($stream, true), Collect::class];
        yield 'Reindex_Collect_3' => [new Reindex(1, 2), new Collect($stream), Reindex::class, Collect::class];
        yield 'Reindex_Collect_4' => [new Reindex(1, 2), new Collect($stream, true), Collect::class];
        yield 'Reindex_Count' => [new Reindex(), new Count($stream), Count::class];
        yield 'Reindex_Gather_1' => [new Reindex(), new Gather(), Gather::class];
        yield 'Reindex_Gather_2' => [new Reindex(1), new Gather(), Reindex::class, Gather::class];
        yield 'Reindex_Gather_3' => [new Reindex(0, 2), new Gather(), Reindex::class, Gather::class];
        yield 'Reindex_Gather_4' => [new Reindex(), new Gather(true), Gather::class];
        yield 'Reindex_Gather_5' => [new Reindex(1), new Gather(true), Gather::class];
        yield 'Reindex_Gather_6' => [new Reindex(0, 2), new Gather(true), Gather::class];
        yield 'Reindex_Segregate_1' => [new Reindex(), new Segregate(), Reindex::class, Segregate::class];
        yield 'Reindex_Segregate_2' => [new Reindex(), new Segregate(null, null, Check::VALUE, true), Segregate::class];
        yield 'Reindex_Segregate_3' => [new Reindex(1, 2), new Segregate(), Reindex::class, Segregate::class];
        yield 'Reindex_Segregate_4' => [
            new Reindex(1, 2), new Segregate(null, null, Check::VALUE, true), Segregate::class
        ];
        yield 'Reindex_UnpackTuple' => [new Reindex(), new UnpackTuple(), UnpackTuple::class];
        yield 'Reindex_Uptrends_1' => [new Reindex(), new Uptrends(), Reindex::class, Uptrends::class];
        yield 'Reindex_Uptrends_2' => [new Reindex(), new Uptrends(null, Check::VALUE, true), Uptrends::class];
        yield 'Reindex_Uptrends_3' => [new Reindex(1, 2), new Uptrends(), Reindex::class, Uptrends::class];
        yield 'Reindex_Uptrends_4' => [
            new Reindex(1, 2), new Uptrends(null, Check::VALUE, true), Uptrends::class
        ];
        
        yield 'Reverse_Count' => [new Reverse(), new Count($stream), Count::class];
        yield 'Reverse_Find' => [new Reverse(), new Find($stream, 'foo'), Find::class];
        yield 'Reverse_First' => [new Reverse(), new First($stream), Last::class];
        yield 'Reverse_Has' => [new Reverse(), new Has($stream, 'foo'), Has::class];
        yield 'Reverse_HasEvery' => [new Reverse(), new HasEvery($stream, ['foo']), HasEvery::class];
        yield 'Reverse_HasOnly' => [new Reverse(), new HasOnly($stream, ['foo']), HasOnly::class];
        yield 'Reverse_Last' => [new Reverse(), new Last($stream), First::class];
        yield 'Reverse_Shuffle' => [new Reverse(), new Shuffle(), Shuffle::class];
        yield 'Reverse_Shuffle_chunked' => [new Reverse(), new Shuffle(3), Reverse::class, Shuffle::class];
        yield 'Reverse_Sort' => [new Reverse(), new Sort(), Sort::class];
        yield 'Reverse_SortLimited' => [new Reverse(), new SortLimited(3), SortLimited::class];
        yield 'Reverse_Tail' => [new Reverse(), new Tail(6), Limit::class, Reverse::class];
        
        yield 'Segregate_equal' => [new Segregate(5), new Tail(5), Segregate::class];
        yield 'Segregate_greater' => [new Segregate(7), new Tail(5), Segregate::class, Skip::class];
        yield 'Segregate_one_Shuffle' => [new Segregate(1), new Shuffle(), Segregate::class];
        yield 'Segregate_Reindex_custom' => [new Segregate(2), new Reindex(1), Segregate::class, Reindex::class];
        yield 'Segregate_Reindex_default' => [new Segregate(2), new Reindex(), Segregate::class];
        
        yield 'SendTo_SendTo' => [new SendTo(Consumers::counter()), new SendTo(Consumers::counter()), SendTo::class];
        
        yield 'Shuffle_Count' => [new Shuffle(), new Count($stream), Count::class];
        yield 'Shuffle_Find' => [new Shuffle(), new Find($stream, 'foo'), Find::class];
        yield 'Shuffle_Has' => [new Shuffle(), new Has($stream, 'foo'), Has::class];
        yield 'Shuffle_HasEvery' => [new Shuffle(), new HasEvery($stream, ['foo']), HasEvery::class];
        yield 'Shuffle_HasOnly' => [new Shuffle(), new HasOnly($stream, ['foo']), HasOnly::class];
        yield 'Shuffle_Reverse' => [new Shuffle(), new Reverse(), Shuffle::class];
        yield 'Shuffle_Shuffle' => [new Shuffle(), new Shuffle(), Shuffle::class];
        yield 'Shuffle_Sort' => [new Shuffle(), new Sort(), Sort::class];
        yield 'Shuffle_SortLimited' => [new Shuffle(), new SortLimited(3), SortLimited::class];
        
        yield 'Sort_Count' => [new Sort(), new Count($stream), Count::class];
        yield 'Sort_Find' => [new Sort(), new Find($stream, 'foo'), Find::class];
        yield 'Sort_First' => [new Sort(), new First($stream), SortLimited::class, First::class];
        yield 'Sort_Has' => [new Sort(), new Has($stream, 'foo'), Has::class];
        yield 'Sort_HasEvery' => [new Sort(), new HasEvery($stream, ['foo']), HasEvery::class];
        yield 'Sort_HasOnly' => [new Sort(), new HasOnly($stream, ['foo']), HasOnly::class];
        yield 'Sort_Last' => [new Sort(), new Last($stream), SortLimited::class, First::class];
        yield 'Sort_Shuffle' => [new Sort(), new Shuffle(), Shuffle::class];
        yield 'Sort_Shuffle_chunked' => [new Sort(), new Shuffle(5), Sort::class, Shuffle::class];
        yield 'Sort_Tail' => [new Sort(), new Tail(5), SortLimited::class, Reverse::class];
        
        yield 'SortLimited_equal' => [new SortLimited(5), new Tail(5), SortLimited::class];
        yield 'SortLimited_greater' => [new SortLimited(7), new Tail(5), SortLimited::class, Skip::class];
        yield 'SortLimited_one_Shuffle' => [new SortLimited(1), new Shuffle(), SortLimited::class];
        yield 'SortLimited_Reverse' => [new SortLimited(1), new Reverse(), SortLimited::class];
        
        yield 'Tail_Last' => [new Tail(3), new Last($stream), Last::class];
        yield 'Tail_Tail' => [new Tail(3), new Tail(2), Tail::class];
        
        yield 'Tokenize_Reindex_custom' => [new Tokenize(' '), new Reindex(1), Tokenize::class, Reindex::class];
        yield 'Tokenize_Reindex_default' => [new Tokenize(' '), new Reindex(), Tokenize::class];
        
        yield 'Tuple_Reindex_custom' => [new Tuple(), new Reindex(1), Tuple::class, Reindex::class];
        yield 'Tuple_Reindex_default' => [new Tuple(), new Reindex(), Tuple::class];
        
        yield 'Unique' => [new Unique(), OmitReps::class, Unique::class];
        
        yield 'UnpackTuple_MakeTuple' => [new UnpackTuple(), new Tuple()];
        
        //special cases
        
        yield 'Reverse_Unique_Shuffle_Filter' => [
            new Reverse(), new Unique(), new Shuffle(), new Filter('is_string'),
            FilterMany::class, Reverse::class, OmitReps::class, Unique::class, Shuffle::class
        ];
        
        yield 'IsEmpty' => [
            new Segregate(3), new Categorize(Discriminators::byKey()), new Classify(Discriminators::byKey()),
            new Chunk(3), new ChunkBy(Discriminators::byField('foo')), new Flip(),
            new MapFieldWhen('foo', 'is_string', Mappers::shuffle()), new Flat(), new OmitReps(),
            new MapKeyValue(static fn($v, $k): array => [$k => $v]), new Gather(), new MapKey(Mappers::value()),
            new Map('strtolower'), new Reindex(), new Reverse(), new Scan(0, Reducers::sum()), new Shuffle(),
            new Tuple(), new Tail(4), new Unique(), new Sort(), new SortLimited(5), new IsEmpty($stream, true),
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
    
    public function getDataForTestChainFlatFlat(): array
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
        $shuffle = new Shuffle($firstChunkSize);
        $this->chainOperations($pipe, $stream, $shuffle, new Shuffle($secondChunkSize));
        
        //then
        self::assertSame($isChunked, $shuffle->isChunked());
    }
    
    public function getDataForTestChainShuffleShuffle(): array
    {
        return [
            [null, null, false],
            [3, null, false],
            [null, 3, false],
            [5, 3, true],
        ];
    }
    
    /**
     * @dataProvider getDataForTestChainLimitableFirst
     */
    public function test_chain_Limitable_First(Limitable $operation): void
    {
        self::assertInstanceOf(Operation::class, $operation);
        
        //given
        $stream = Stream::empty();
        $pipe = $this->getPipeFromStream($stream);
        
        //when
        $this->chainOperations($pipe, $stream, $operation, new First($stream));
        
        //then
        self::assertSame(1, $operation->limit());
    }
    
    public function getDataForTestChainLimitableFirst(): array
    {
        //only these two
        return [
            [new Segregate(5)],
            [new SortLimited(5)],
        ];
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
    
    public function getDataForTestStackedIsEmpty(): array
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
    public function test_stacked_hasOnly(int $mode, string $operation): void
    {
        //given
        [$stream, $pipe, $signal] = $this->prepare();
        
        $hasOnly = new HasOnly($stream, [2, 3], $mode);
        $this->addOperations($pipe, $this->createOperation($operation), $hasOnly);
        
        //when
        $this->sendToPipe([6, 2, 3, 8], $pipe, $signal);
        
        //then
        self::assertFalse($hasOnly->get());
    }
    
    /**
     * @dataProvider createAllOperationModeVariations
     */
    public function test_stacked_hasEvery(int $mode, string $operation): void
    {
        //given
        [$stream, $pipe, $signal] = $this->prepare();
        
        $hasEvery = new HasEvery($stream, [2, 4], $mode);
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

        $hasEvery = new HasEvery($stream, [2, 4], $mode);
        $this->addOperations($pipe, $this->createOperation($operation), $hasEvery);

        //when
        $this->sendToPipe([6, 2, 3, 8], $pipe, $signal);

        //then
        self::assertFalse($hasEvery->get());
    }
    
    public function createAllOperationModeVariations(): \Generator
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
        
        $pipe = new Pipe();
        $pipe->chainOperation(new Filter(Filters::greaterThan(5)), $stream);
        $pipe->chainOperation(new CollectIn($collector), $stream);
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
        self::assertSame([8, 6, 9, 7, 6], $collector->getData());
    }
    
    public function test_pipe_cannot_be_cloned_when_its_stack_is_not_empty(): void
    {
        //Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot clone Pipe with non-empty stack');
        
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
        $accumulate = new Accumulate('is_string');
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
        [$stream, $pipe, $signal] = $this->prepare();
        
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
    
    private function createOperation(string $name): Operation
    {
        switch ($name) {
            case 'gather': return new Gather();
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
        return $this->getPropertyFromObject($stream, 'pipe');
    }
    
    private function getSignalFromStream(Stream $stream): Signal
    {
        return $this->getPropertyFromObject($stream, 'signal');
    }
    
    private function getPropertyFromObject(object $object, string $property)
    {
        $prop = (new \ReflectionObject($object))->getProperty($property);
        $prop->setAccessible(true);
        
        return $prop->getValue($object);
    }
}