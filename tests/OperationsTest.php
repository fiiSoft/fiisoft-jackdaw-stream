<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Collector\Collectors;
use FiiSoft\Jackdaw\Comparator\Comparator;
use FiiSoft\Jackdaw\Comparator\Comparators;
use FiiSoft\Jackdaw\Consumer\Consumers;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Pipe;
use FiiSoft\Jackdaw\Internal\ResultItem;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Mapper\Mappers;
use FiiSoft\Jackdaw\Operation\Aggregate;
use FiiSoft\Jackdaw\Operation\Chunk;
use FiiSoft\Jackdaw\Operation\Classify;
use FiiSoft\Jackdaw\Operation\CollectIn;
use FiiSoft\Jackdaw\Operation\Flat;
use FiiSoft\Jackdaw\Operation\Dispatch;
use FiiSoft\Jackdaw\Operation\Internal\Dispatcher\Handlers;
use FiiSoft\Jackdaw\Operation\Internal\Ending;
use FiiSoft\Jackdaw\Operation\Internal\FeedMany;
use FiiSoft\Jackdaw\Operation\Internal\Fork;
use FiiSoft\Jackdaw\Operation\Internal\Initial;
use FiiSoft\Jackdaw\Operation\Limit;
use FiiSoft\Jackdaw\Operation\MapFieldWhen;
use FiiSoft\Jackdaw\Operation\MapKey;
use FiiSoft\Jackdaw\Operation\MapKeyValue;
use FiiSoft\Jackdaw\Operation\Operation;
use FiiSoft\Jackdaw\Operation\Reverse;
use FiiSoft\Jackdaw\Operation\Segregate;
use FiiSoft\Jackdaw\Operation\SendTo;
use FiiSoft\Jackdaw\Operation\SendToMax;
use FiiSoft\Jackdaw\Operation\Sort;
use FiiSoft\Jackdaw\Operation\SortLimited;
use FiiSoft\Jackdaw\Operation\State\SortLimited\BufferFull;
use FiiSoft\Jackdaw\Operation\State\SortLimited\SingleItem;
use FiiSoft\Jackdaw\Operation\StoreIn;
use FiiSoft\Jackdaw\Operation\Tail;
use FiiSoft\Jackdaw\Operation\Terminating\Collect;
use FiiSoft\Jackdaw\Operation\Terminating\CollectKeys;
use FiiSoft\Jackdaw\Operation\Terminating\Count;
use FiiSoft\Jackdaw\Operation\Terminating\Find;
use FiiSoft\Jackdaw\Operation\Terminating\First;
use FiiSoft\Jackdaw\Operation\Terminating\GroupBy;
use FiiSoft\Jackdaw\Operation\Terminating\Has;
use FiiSoft\Jackdaw\Operation\Terminating\HasEvery;
use FiiSoft\Jackdaw\Operation\Terminating\Last;
use FiiSoft\Jackdaw\Operation\Terminating\Reduce;
use FiiSoft\Jackdaw\Producer\Producers;
use FiiSoft\Jackdaw\Reducer\Reducers;
use FiiSoft\Jackdaw\Stream;
use PHPUnit\Framework\TestCase;

final class OperationsTest extends TestCase
{
    public function test_Tail_throws_exception_when_limit_is_invalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param length');
        
        new Tail(0);
    }
    
    public function test_SendToMax_throws_exception_when_limit_is_invalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param times');
        
        new SendToMax(0, Consumers::counter());
    }
    
    /**
     * @dataProvider getDataForTestAggregateThrowsExceptionWhenParamKeysIsInvalid
     */
    public function test_Aggregate_throws_exception_when_param_keys_is_invalid($keys): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param keys');
        
        new Aggregate($keys);
    }
    
    public function getDataForTestAggregateThrowsExceptionWhenParamKeysIsInvalid(): array
    {
        return [
            [[]],
            [[true]],
            [[new \stdClass()]],
            [[12.0]],
            [['']],
            [[[]]],
        ];
    }
    
    public function test_Flat_has_limit_of_levels(): void
    {
        $flat = new Flat();
        $maxLevel = $flat->maxLevel();
        
        $flat->mergeWith(new Flat());
        self::assertSame($maxLevel, $flat->maxLevel());
    }
    
    public function test_Flat_does_not_pass_signal_when_iterable_is_empty(): void
    {
        $counter = Consumers::counter();
        Stream::from([[]])->flat()->call($counter)->run();
        
        self::assertSame(0, $counter->count());
    }
    
    public function test_MapFieldWhen_throws_exception_when_param_field_is_invalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param field');
        
        new MapFieldWhen('', 'is_string', 'strtolower');
    }
    
    public function test_MapFieldWhen_throws_exception_when_field_is_not_in_ArrayAccess_object(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Field foo does not exist in value');
        
        Stream::from([['bar' => 1]])
            ->map(static fn(array $row): \ArrayObject => new \ArrayObject($row))
            ->mapFieldWhen('foo', 'is_string', 'strtoupper')
            ->run();
    }
    
    public function test_SortLimited_throws_exception_when_param_limit_is_invalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param limit');
        
        new SortLimited(0);
    }
    
    public function test_SortLimited_acceptSimpleData(): void
    {
        //given
        [$stream, $pipe, $signal] = $this->prepare();
        
        $sortLimited = new SortLimited(3);
        $collect = new Collect($stream);
        $this->addToPipe($pipe, $sortLimited, $collect);
        
        //when
        $sortLimited->acceptSimpleData([5, 2, 7, 1, 3, 6], $signal, false);
        
        //then
        self::assertSame([3 => 1, 1 => 2, 4 => 3], $collect->get());
    }
    
    public function test_change_size_of_SortLimited_buffer_is_prohibited(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Change of size of full buffer is prohibited');
        
        $state = new BufferFull(new SortLimited(5), new \SplMaxHeap());
        $state->setLength(1);
    }
    
    public function test_Ending_operation(): void
    {
        $operation = new Ending();
        
        self::assertFalse($operation->streamingFinished(new Signal(Stream::empty())));
    }
    
    public function test_Ending_operation_cannot_be_removed_from_chain(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('It should never happen (Ending::removeFromChain)');
    
        $operation = new Ending();
        $operation->removeFromChain();
    }
    
    public function test_Ending_operation_has_to_be_the_last_operation_in_chain(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('It should never happen (Ending::setNext)');
    
        $operation = new Ending();
        $operation->setNext(new Ending());
    }
    
    public function test_Ending_cannot_prepend_other_operation(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('It should never happen (Ending::prepend)');
        
        $operation = new Ending();
        $operation->prepend(new Ending());
    }
    
    public function test_Initial_operation_have_to_be_first_operation_in_chain(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('It should never happen (Inital::setPrev)');
    
        $operation = new Initial();
        $operation->setPrev(new Ending());
    }
    
    public function test_Initial_cannot_prepend_other_operation(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('It should never happen (Inital::prepend)');
    
        $operation = new Initial();
        $operation->prepend(new Ending());
    }
    
    public function test_Initial_operation_passes_signal_to_next_operation(): void
    {
        //given
        $flag = false;
        $initial = new Initial();
        
        $initial->setNext(new SendTo(static function () use (&$flag): void {
            $flag = true;
        }));
        
        $signal = new Signal(Stream::empty());
        $signal->item->key = 'a';
        $signal->item->value = 'a';
        
        //when
        $initial->handle($signal);
        
        //then
        self::assertTrue($flag);
    }
    
    public function test_Initial_operation_passes_signal_to_next_operation_on_the_end_of_streaming(): void
    {
        //given
        $passedData = [];
        
        $initial = new Initial();
        $chunk = new Chunk(5, true);
        
        $initial->setNext($chunk);
        $chunk->setNext(new SendTo(static function ($value, $key) use (&$passedData): void {
            $passedData[$key] = $value;
        }));
        
        $signal = new Signal(Stream::empty());
        $signal->item->key = 'a';
        
        //when
        $signal->item->value = 3;
        $initial->handle($signal);
        
        $signal->item->value = 7;
        $initial->handle($signal);
        
        $signal->isEmpty = true;
        $initial->streamingFinished($signal);
        
        //then
        self::assertSame([[3, 7]], $passedData);
    }
    
    public function test_FeedMany_throws_exception_when_no_streams_are_provided(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('FeedMany requires at least one stream');
        
        new FeedMany();
    }
    
    public function test_MapKey_can_merge_Value_with_FieldValue(): void
    {
        $first = new MapKey(Mappers::value());
        $second = new MapKey(Mappers::fieldValue('foo'));
        
        self::assertTrue($first->mergeWith($second));
        self::assertFalse($first->mergeWith(new MapKey(Mappers::simple('bar'))));
    }
    
    public function test_MapKey_can_merge_FieldValue_with_Value(): void
    {
        $first = new MapKey(Mappers::fieldValue('foo'));
        $second = new MapKey(Mappers::value());
        
        self::assertTrue($first->mergeWith($second));
    }
    
    public function test_MapKeyValue_throws_exception_when_number_of_arguments_accepted_by_mapper_is_invalid(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('KeyValue mapper have to accept 0, 1 or 2 arguments, but requires 3');
        
        new MapKeyValue(static fn($a, $b, $c): bool => true);
    }
    
    public function test_Fork_throws_exception_when_Discriminator_returns_invalid_value(): void
    {
        //Assert
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unsupported value was returned from discriminator (got array)');
        
        //Arrange
        $stream = Stream::empty();
        
        $fork = new Fork(static fn($v,$k): array => [$k => $v], new Collect($stream));
        
        //Act
        $fork->handle(new Signal($stream));
    }
    
    /**
     * @dataProvider getDataForTestMapKeyValueThrowsExceptionWhenDeclaredTypeOfValueOfMapperIsNotArray
     */
    public function test_MapKeyValue_throws_exception_when_return_type_of_mapper_is_not_array(callable $mapper): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('KeyValue mapper must have declared array as its return type');
        
        new MapKeyValue($mapper);
    }
    
    public function getDataForTestMapKeyValueThrowsExceptionWhenDeclaredTypeOfValueOfMapperIsNotArray(): \Generator
    {
        $mappers = [
            static fn(): bool => true,
            static fn(): string => 'wrong',
            static fn() => [],
            static function () {
                return [];
            }
        ];
    
        foreach ($mappers as $mapper) {
            yield [$mapper];
        }
    }
    
    public function test_First_basic(): void
    {
        [$stream, $pipe, $signal] = $this->prepare();
        
        $first = new First($stream);
        $this->addToPipe($pipe, $first);
        
        $this->sendToPipe([8, 3, 5], $pipe, $signal);
        self::assertSame(8, $first->get());
    }
    
    public function test_First_collectDataFromProducer(): void
    {
        [$stream, , $signal] = $this->prepare();
        
        $first = new First($stream);
        $first->setNext(new Ending());
        $first->collectDataFromProducer(Producers::getAdapter([8, 3, 5]), $signal, false);
        
        self::assertSame(8, $first->get());
    }
    
    public function test_First_acceptSimpleData(): void
    {
        //given
        [$stream, , $signal] = $this->prepare();
        
        $first = new First($stream);
        $first->setNext(new Ending());
        
        //when
        $first->acceptSimpleData([8, 3, 5], $signal, false);
        
        //then
        self::assertSame(8, $first->get());
    }
    
    public function test_First_acceptCollectedItems(): void
    {
        //given
        [$stream, , $signal] = $this->prepare();
        
        $first = new First($stream);
        $first->setNext(new Ending());
        
        //when
        $first->acceptCollectedItems($this->convertToItems([8, 3, 5]), $signal, false);
        
        //then
        self::assertSame(8, $first->get());
    }
    
    /**
     * @dataProvider getDataForCollect
     */
    public function test_Collect_basic(bool $reindex, array $dataSet, array $expected): void
    {
        [$stream, $pipe, $signal] = $this->prepare();
        
        $collect = new Collect($stream, $reindex);
        $this->addToPipe($pipe, $collect);
        
        $this->sendToPipe($dataSet, $pipe, $signal);
        self::assertSame($expected, $collect->get());
    }
    
    /**
     * @dataProvider getDataForCollect
     */
    public function test_Collect_collectDataFromProducer(bool $reindex, array $dataSet, array $expected): void
    {
        //given
        [$stream, , $signal] = $this->prepare();
        
        $collect = new Collect($stream, $reindex);
        $collect->setNext(new Ending());
        
        //when
        $collect->collectDataFromProducer(Producers::getAdapter($dataSet), $signal, false);
        
        //then
        self::assertSame($expected, $collect->get());
    }
    
    /**
     * @dataProvider getDataForCollect
     */
    public function test_Collect_acceptSimpleData(bool $reindex, array $dataSet, array $expected): void
    {
        //given
        [$stream, , $signal] = $this->prepare();
        
        $collect = new Collect($stream, $reindex);
        $collect->setNext(new Ending());
        
        //when
        $collect->acceptSimpleData($dataSet, $signal, false);
        
        //then
        self::assertSame($expected, $collect->get());
    }
    
    /**
     * @dataProvider getDataForCollect
     */
    public function test_Collect_acceptCollectedItems(bool $reindex, array $dataSet, array $expected): void
    {
        //given
        [$stream, , $signal] = $this->prepare();
        
        $collect = new Collect($stream, $reindex);
        $collect->setNext(new Ending());
        
        //when
        $collect->acceptCollectedItems($this->convertToItems($dataSet), $signal, false);
        
        //then
        self::assertSame($expected, $collect->get());
    }
    
    public function getDataForCollect(): array
    {
        //reindex, dataset, expected keys
        return [
            [false, [], []],
            [true, [], []],
            [true, ['a' => 1, 5 => 'c'], [1, 'c']],
            [false, ['a' => 1, 5 => 'c'], ['a' => 1, 5 => 'c']],
        ];
    }
    
    /**
     * @dataProvider getDataForCollectKeys
     */
    public function test_CollectKeys_basic(array $dataSet, array $expected): void
    {
        [$stream, $pipe, $signal] = $this->prepare();
        
        $collectKeys = new CollectKeys($stream);
        $this->addToPipe($pipe, $collectKeys);
        
        $this->sendToPipe($dataSet, $pipe, $signal);
        self::assertSame($expected, $collectKeys->get());
    }
    
    /**
     * @dataProvider getDataForCollectKeys
     */
    public function test_CollectKeys_collectDataFromProducer(array $dataSet, array $expected): void
    {
        //given
        [$stream, , $signal] = $this->prepare();
        
        $collectKeys = new CollectKeys($stream);
        $collectKeys->setNext(new Ending());
        
        //when
        $collectKeys->collectDataFromProducer(Producers::getAdapter($dataSet), $signal, false);
        
        //then
        self::assertSame($expected, $collectKeys->get());
    }
    
    /**
     * @dataProvider getDataForCollectKeys
     */
    public function test_CollectKeys_acceptSimpleData(array $dataSet, array $expected): void
    {
        //given
        [$stream, , $signal] = $this->prepare();
        
        $collectKeys = new CollectKeys($stream);
        $collectKeys->setNext(new Ending());
        
        //when
        $collectKeys->acceptSimpleData($dataSet, $signal, false);
        
        //then
        self::assertSame($expected, $collectKeys->get());
    }
    
    /**
     * @dataProvider getDataForCollectKeys
     */
    public function test_CollectKeys_acceptCollectedItems(array $dataSet, array $expected): void
    {
        //given
        [$stream, , $signal] = $this->prepare();
        
        $collectKeys = new CollectKeys($stream);
        $collectKeys->setNext(new Ending());
        
        //when
        $collectKeys->acceptCollectedItems($this->convertToItems($dataSet), $signal, false);
        
        //then
        self::assertSame($expected, $collectKeys->get());
    }
    
    public function getDataForCollectKeys(): array
    {
        //dataset, expected keys
        return [
            [[], []],
            [['a'], [0]],
            [['a' => 1, 5 => 'c'], ['a', 5]],
        ];
    }
    
    /**
     * @dataProvider getDataForCount
     */
    public function test_Count_basic(array $dataSet, int $expected): void
    {
        [$stream, $pipe, $signal] = $this->prepare();
        
        $count = new Count($stream);
        $this->addToPipe($pipe, $count);
        
        $this->sendToPipe($dataSet, $pipe, $signal);
        self::assertSame($expected, $count->get());
    }
    
    /**
     * @dataProvider getDataForCount
     */
    public function test_Count_collectDataFromProducer(array $dataSet, int $expected): void
    {
        //given
        [$stream, , $signal] = $this->prepare();
        
        $count = new Count($stream);
        $count->setNext(new Ending());
        
        //when
        $count->collectDataFromProducer(Producers::getAdapter($dataSet), $signal, false);
        
        //then
        self::assertSame($expected, $count->get());
    }
    
    public function test_Count_collectDataFromProducer_non_countable(): void
    {
        //given
        [$stream, , $signal] = $this->prepare();
        
        $count = new Count($stream);
        $count->setNext(new Ending());
        
        //when
        $count->collectDataFromProducer(Producers::tokenizer(' ', 'foo bar zoo'), $signal, false);
        
        //then
        self::assertSame(3, $count->get());
    }
    
    /**
     * @dataProvider getDataForCount
     */
    public function test_Count_acceptSimpleData(array $dataSet, int $expected): void
    {
        //given
        [$stream, , $signal] = $this->prepare();
        
        $count = new Count($stream);
        $count->setNext(new Ending());
        
        //when
        $count->acceptSimpleData($dataSet, $signal, false);
        
        //then
        self::assertSame($expected, $count->get());
    }
    
    /**
     * @dataProvider getDataForCount
     */
    public function test_Count_acceptCollectedItems(array $dataSet, int $expected): void
    {
        //given
        [$stream, , $signal] = $this->prepare();
        
        $count = new Count($stream);
        $count->setNext(new Ending());
        
        //when
        $count->acceptCollectedItems($this->convertToItems($dataSet), $signal, false);
        
        //then
        self::assertSame($expected, $count->get());
    }
    
    public function getDataForCount(): array
    {
        //dataset, expected count
        return [
            [[], 0],
            [['a'], 1],
            [['a', 'b', 'c'], 3],
        ];
    }
    
    /**
     * @dataProvider getDataForTestHas
     */
    public function test_Has_basic(int $mode, $predicate, array $dataSet, bool $expected): void
    {
        [$stream, $pipe, $signal] = $this->prepare();
        
        $has = new Has($stream, $predicate, $mode);
        $this->addToPipe($pipe, $has);
        
        $this->sendToPipe($dataSet, $pipe, $signal);
        self::assertSame($expected, $has->get());
    }
    
    /**
     * @dataProvider getDataForTestHas
     */
    public function test_Has_collectDataFromProducer(int $mode, $predicate, array $dataSet, bool $expected): void
    {
        //given
        [$stream, , $signal] = $this->prepare();
        
        $has = new Has($stream, $predicate, $mode);
        $has->setNext(new Ending());
        
        //when
        $has->collectDataFromProducer(Producers::getAdapter($dataSet), $signal, false);
        
        //then
        self::assertSame($expected, $has->get());
    }
    
    /**
     * @dataProvider getDataForTestHas
     */
    public function test_Has_acceptSimpleData(int $mode, $predicate, array $dataSet, bool $expected): void
    {
        //given
        [$stream, , $signal] = $this->prepare();
        
        $has = new Has($stream, $predicate, $mode);
        $has->setNext(new Ending());
        
        //when
        $has->acceptSimpleData($dataSet, $signal, false);
        
        //then
        self::assertSame($expected, $has->get());
    }
    
    /**
     * @dataProvider getDataForTestHas
     */
    public function test_Has_acceptCollectedItems(int $mode, $predicate, array $dataSet, bool $expected): void
    {
        //given
        [$stream, , $signal] = $this->prepare();
        
        $has = new Has($stream, $predicate, $mode);
        $has->setNext(new Ending());
        
        //when
        $has->acceptCollectedItems($this->convertToItems($dataSet), $signal, false);
        
        //then
        self::assertSame($expected, $has->get());
    }
    
    public function getDataForTestHas(): \Generator
    {
        $cases = [
            [Filters::greaterOrEqual(5), false],
            [3, true],
        ];
        
        $dataSet = [1, 2, 3 => 3, 4];
        
        yield from $this->generateVariationsWithValues($cases, $dataSet);
    }
    
    /**
     * @dataProvider getDataForTestFind
     */
    public function test_Find_basic(int $mode, $predicate, array $dataSet, ?array $expected): void
    {
        [$stream, $pipe, $signal] = $this->prepare();
        
        $find = new Find($stream, $predicate, $mode);
        $this->addToPipe($pipe, $find);
        
        $this->sendToPipe($dataSet, $pipe, $signal);
        
        $this->assertFindResultIsCorrect($find, $expected);
    }
    
    /**
     * @dataProvider getDataForTestFind
     */
    public function test_Find_collectDataFromProducer(int $mode, $predicate, array $dataSet, ?array $expected): void
    {
        //given
        [$stream, , $signal] = $this->prepare();

        $find = new Find($stream, $predicate, $mode);
        $find->setNext(new Ending());
        
        //when
        $find->collectDataFromProducer(Producers::getAdapter($dataSet), $signal, false);
        
        //then
        $this->assertFindResultIsCorrect($find, $expected);
    }
    
    /**
     * @dataProvider getDataForTestFind
     */
    public function test_Find_acceptSimpleData(int $mode, $predicate, array $dataSet, ?array $expected): void
    {
        //given
        [$stream, , $signal] = $this->prepare();
        
        $find = new Find($stream, $predicate, $mode);
        $find->setNext(new Ending());
        
        //when
        $find->acceptSimpleData($dataSet, $signal, false);
        
        //then
        $this->assertFindResultIsCorrect($find, $expected);
    }
    
    /**
     * @dataProvider getDataForTestFind
     */
    public function test_Find_acceptCollectedItems(int $mode, $predicate, array $dataSet, ?array $expected): void
    {
        //given
        [$stream, , $signal] = $this->prepare();
        
        $find = new Find($stream, $predicate, $mode);
        $find->setNext(new Ending());
        
        //when
        $find->acceptCollectedItems($this->convertToItems($dataSet), $signal, false);
        
        //then
        $this->assertFindResultIsCorrect($find, $expected);
    }
    
    private function assertFindResultIsCorrect(Find $find, ?array $expected): void
    {
        self::assertSame($expected !== null, $find->hasResult());
        
        if ($expected !== null) {
            self::assertSame($expected[0], $find->key());
            self::assertSame($expected[1], $find->get());
        }
    }
    
    public function getDataForTestFind(): array
    {
        $dataSet = [1 => 'a', 2, 'b' => 3, 4];
        
        //mode, predicate, dataSet, expected[key,value]
        return [
            [Check::VALUE, 'is_int', $dataSet, [2, 2]],
            [Check::VALUE, 'is_string', $dataSet, [1, 'a']],
            [Check::KEY, 'is_int', $dataSet, [1, 'a']],
            [Check::KEY, 'is_string', $dataSet, ['b', 3]],
            [Check::ANY, 'is_int', $dataSet, [1, 'a']],
            [Check::ANY, 'is_string', $dataSet, [1, 'a']],
            [Check::BOTH, 'is_int', $dataSet, [2, 2]],
            [Check::BOTH, 'is_string', $dataSet, null],
        ];
    }
    
    /**
     * @dataProvider getDataForTestReduce
     */
    public function test_Reduce_basic(array $dataSet, ?string $expected): void
    {
        [$stream, $pipe, $signal] = $this->prepare();
        
        $reduce = new Reduce($stream, Reducers::concat());
        $this->addToPipe($pipe, $reduce);
        
        $this->sendToPipe($dataSet, $pipe, $signal);
        self::assertSame($expected, $reduce->get());
    }
    
    /**
     * @dataProvider getDataForTestReduce
     */
    public function test_Reduce_collectDataFromProducer(array $dataSet, ?string $expected): void
    {
        //given
        [$stream, , $signal] = $this->prepare();

        $reduce = new Reduce($stream, Reducers::concat());
        $reduce->setNext(new Ending());
        
        //when
        $reduce->collectDataFromProducer(Producers::getAdapter($dataSet), $signal, false);
        
        //then
        self::assertSame($expected, $reduce->get());
    }
    
    /**
     * @dataProvider getDataForTestReduce
     */
    public function test_Reduce_acceptSimpleData(array $dataSet, ?string $expected): void
    {
        //given
        [$stream, , $signal] = $this->prepare();
        
        $reduce = new Reduce($stream, Reducers::concat());
        $reduce->setNext(new Ending());
        
        //when
        $reduce->acceptSimpleData($dataSet, $signal, false);
        
        //then
        self::assertSame($expected, $reduce->get());
    }
    
    /**
     * @dataProvider getDataForTestReduce
     */
    public function test_Reduce_acceptCollectedItems(array $dataSet, ?string $expected): void
    {
        //given
        [$stream, , $signal] = $this->prepare();
        
        $reduce = new Reduce($stream, Reducers::concat());
        $reduce->setNext(new Ending());
        
        //when
        $reduce->acceptCollectedItems($this->convertToItems($dataSet), $signal, false);
        
        //then
        self::assertSame($expected, $reduce->get());
    }
    
    public function getDataForTestReduce(): array
    {
        return [
            //dataSet, expected
            [['a', 'b', 'c', 'd'], 'abcd'],
            [[], null],
        ];
    }
    
    public function test_Tail_with_Collect(): void
    {
        [$stream, $pipe, $signal] = $this->prepare();
        
        $collect = new Collect($stream);
        $this->addToPipe($pipe, new Tail(2), $collect);
        
        $this->sendToPipe(['a', 'b', 'c', 'd'], $pipe, $signal);
        self::assertSame([2 => 'c', 'd'], $collect->get());
    }
    
    public function test_Tail_collectDataFromProducer_with_Collect(): void
    {
        //given
        [$stream, $pipe, $signal] = $this->prepare();

        $tail = new Tail(2);
        $collect = new Collect($stream);
        $this->addToPipe($pipe, $tail, $collect);
        
        //when
        $tail->collectDataFromProducer(Producers::getAdapter(['a', 'b', 'c', 'd']), $signal, false);
        
        //then
        self::assertSame([2 => 'c', 'd'], $collect->get());
    }
    
    public function test_Tail_acceptSimpleData_with_Collect(): void
    {
        //given
        [$stream, $pipe, $signal] = $this->prepare();
        
        $tail = new Tail(2);
        $collect = new Collect($stream);
        $this->addToPipe($pipe, $tail, $collect);
        
        //when
        $tail->acceptSimpleData(['a', 'b', 'c', 'd'], $signal, false);
        
        //then
        self::assertSame([2 => 'c', 'd'], $collect->get());
    }
    
    public function test_Tail_acceptCollectedItems_with_Collect(): void
    {
        //given
        [$stream, $pipe, $signal] = $this->prepare();
        
        $tail = new Tail(2);
        $collect = new Collect($stream);
        $this->addToPipe($pipe, $tail, $collect);
        
        //when
        $tail->acceptCollectedItems($this->convertToItems(['a', 'b', 'c', 'd']), $signal, false);
        
        //then
        self::assertSame([2 => 'c', 'd'], $collect->get());
    }
    
    /**
     * @dataProvider getDataForTestSort
     */
    public function test_Sort_acceptSimpleData_with_DataCollector(
        bool $reversed,
        int $mode,
        ?Comparator $comparator,
        array $expected
    ): void
    {
        //given
        [$stream, $pipe, $signal] = $this->prepare();
        
        $sort = new Sort($comparator, $mode, $reversed);
        $collect = new Collect($stream);
        $this->addToPipe($pipe, $sort, $collect);
        
        $data = [4 => 5, 8 => 2, 2 => 6, 3 => 5, 7 => 3, 5 => 2, 6 => 1, 1 => 3, 0 => 6];
        
        //when
        $sort->acceptSimpleData($data, $signal, false);
        
        //then
        self::assertSame($expected, $collect->get());
    }
    
    public function getDataForTestSort(): array
    {
        //reversed, mode, Comparator, expected
        return [
            [
                false, Check::VALUE, null,
                [6 => 1, 8 => 2, 5 => 2, 7 => 3, 1 => 3, 4 => 5, 3 => 5, 2 => 6, 0 => 6]
            ], [
                true, Check::VALUE, null,
                [0 => 6, 2 => 6, 4 => 5, 3 => 5, 7 => 3, 1 => 3, 8 => 2, 5 => 2, 6 => 1]
            ], [
                false, Check::VALUE, Comparators::default(),
                [6 => 1, 8 => 2, 5 => 2, 7 => 3, 1 => 3, 4 => 5, 3 => 5, 2 => 6, 0 => 6]
            ], [
                true, Check::VALUE, Comparators::default(),
                [0 => 6, 2 => 6, 4 => 5, 3 => 5, 7 => 3, 1 => 3, 8 => 2, 5 => 2, 6 => 1]
            ], [
                false, Check::KEY, null,
                [0 => 6, 1 => 3, 2 => 6, 3 => 5, 4 => 5, 5 => 2, 6 => 1, 7 => 3, 8 => 2]
            ], [
                true, Check::KEY, null,
                [8 => 2, 7 => 3, 6 => 1, 5 => 2, 4 => 5, 3 => 5, 2 => 6, 1 => 3, 0 => 6]
            ], [
                false, Check::KEY, Comparators::default(),
                [0 => 6, 1 => 3, 2 => 6, 3 => 5, 4 => 5, 5 => 2, 6 => 1, 7 => 3, 8 => 2]
            ], [
                true, Check::KEY, Comparators::default(),
                [8 => 2, 7 => 3, 6 => 1, 5 => 2, 4 => 5, 3 => 5, 2 => 6, 1 => 3, 0 => 6]
            ], [
                false, Check::BOTH, null,
                [6 => 1, 5 => 2, 8 => 2, 1 => 3, 7 => 3, 3 => 5, 4 => 5, 0 => 6, 2 => 6]
            ], [
                true, Check::BOTH, null,
                [2 => 6, 0 => 6, 4 => 5, 3 => 5, 7 => 3, 1 => 3, 8 => 2, 5 => 2, 6 => 1]
            ], [
                false, Check::BOTH, Comparators::default(),
                [6 => 1, 5 => 2, 8 => 2, 1 => 3, 7 => 3, 3 => 5, 4 => 5, 0 => 6, 2 => 6]
            ], [
                true, Check::BOTH, Comparators::default(),
                [2 => 6, 0 => 6, 4 => 5, 3 => 5, 7 => 3, 1 => 3, 8 => 2, 5 => 2, 6 => 1]
            ],
        ];
    }
    
    public function test_Sort_acceptSimpleData_with_single_data(): void
    {
        //given
        [, $pipe, $signal] = $this->prepare();
        
        $sort = new Sort(null, Check::VALUE, false);
        $this->addToPipe($pipe, $sort, new CollectIn(Collectors::default()));
        
        $signal->streamIsEmpty();
        
        //when
        $result = $sort->acceptSimpleData([4 => 5], $signal, false);
        
        //then
        self::assertTrue($result);
        self::assertTrue($signal->isWorking);
        self::assertFalse($signal->isEmpty);
    }
    
    public function test_Sort_acceptCollectedItems_without_DataCollector(): void
    {
        //given
        [, $pipe, $signal] = $this->prepare();
        
        $sort = new Sort(null, Check::VALUE, false);
        $this->addToPipe($pipe, $sort, new CollectIn(Collectors::default()));
        
        $signal->streamIsEmpty();
        
        //when
        $result = $sort->acceptCollectedItems($this->convertToItems([4 => 5]), $signal, false);
        
        //then
        self::assertTrue($result);
        self::assertTrue($signal->isWorking);
        self::assertFalse($signal->isEmpty);
    }
    
    /**
     * @dataProvider getDataForTestReverseAcceptSimpleData
     */
    public function test_Reverse_acceptSimpleData(array $data, array $expected): void
    {
        //given
        [$stream, $pipe, $signal] = $this->prepare();
        
        $reverse = new Reverse();
        $collect = new Collect($stream);
        $this->addToPipe($pipe, $reverse, $collect);
        
        //when
        $reverse->acceptSimpleData($data, $signal, false);
        
        //then
        self::assertSame($expected, $collect->get());
    }
    
    public function getDataForTestReverseAcceptSimpleData(): array
    {
        //data, expected
        return [
            [['a', 'b', 'c'], [2 => 'c', 1 => 'b', 0 => 'a']],
            [[], []],
        ];
    }
    
    public function test_Reverse_acceptSimpleData_reindexed(): void
    {
        //given
        [$stream, $pipe, $signal] = $this->prepare();
        
        $reverse = new Reverse();
        $collect = new Collect($stream);
        $this->addToPipe($pipe, $reverse, $collect);
        
        //when
        $reverse->acceptSimpleData(['a', 'b', 'c'], $signal, true);
        
        //then
        self::assertSame([2 => 'c', 1 => 'b', 0 => 'a'], $collect->get());
    }
    
    public function test_Reverse_acceptSimpleData_with_not_DataCollector_next_in_chain(): void
    {
        //given
        [, $pipe, $signal] = $this->prepare();
        
        $reverse = new Reverse();
        $this->addToPipe($pipe, $reverse, new Chunk(2));
        
        $signal->streamIsEmpty();
        
        //when
        $result = $reverse->acceptSimpleData([1, 2, 3], $signal, false);
        
        //then
        self::assertTrue($result);
        self::assertTrue($signal->isWorking);
        self::assertFalse($signal->isEmpty);
    }
    
    /**
     * @dataProvider getDataForTestReverseAcceptSimpleData
     */
    public function test_Reverse_acceptCollectedItems(array $data, array $expected): void
    {
        //given
        [$stream, $pipe, $signal] = $this->prepare();
        
        $reverse = new Reverse();
        $collect = new Collect($stream);
        $this->addToPipe($pipe, $reverse, $collect);
        
        //when
        $reverse->acceptCollectedItems($this->convertToItems($data), $signal, false);
        
        //then
        self::assertSame($expected, $collect->get());
    }
    
    public function test_Reverse_acceptCollectedItems_with_not_DataCollector_next_in_chain(): void
    {
        //given
        [, $pipe, $signal] = $this->prepare();
        
        $reverse = new Reverse();
        $this->addToPipe($pipe, $reverse, new Chunk(2));
        
        $signal->streamIsEmpty();
        
        //when
        $result = $reverse->acceptCollectedItems($this->convertToItems([1, 2, 3]), $signal, false);
        
        //then
        self::assertTrue($result);
        self::assertTrue($signal->isWorking);
        self::assertFalse($signal->isEmpty);
    }
    
    /**
     * @dataProvider getDataForTestHasEvery
     */
    public function test_HasEvery_basic(int $mode, array $values, array $dataSet, bool $expected): void
    {
        [$stream, $pipe, $signal] = $this->prepare();
        
        $hasEvery = new HasEvery($stream, $values, $mode);
        $this->addToPipe($pipe, $hasEvery);
        
        $this->sendToPipe($dataSet, $pipe, $signal);
        self::assertSame($expected, $hasEvery->get());
    }
    
    /**
     * @dataProvider getDataForTestHasEvery
     */
    public function test_HasEvery_collectDataFromProducer(int $mode, array $values, array $dataSet, bool $expected): void
    {
        //given
        [$stream, , $signal] = $this->prepare();

        $hasEvery = new HasEvery($stream, $values, $mode);
        $hasEvery->setNext(new Ending());
        
        //when
        $hasEvery->collectDataFromProducer(Producers::getAdapter($dataSet), $signal, false);
        
        //then
        self::assertSame($expected, $hasEvery->get());
    }
    
    /**
     * @dataProvider getDataForTestHasEvery
     */
    public function test_HasEvery_acceptSimpleData(int $mode, array $values, array $dataSet, bool $expected): void
    {
        //given
        [$stream, , $signal] = $this->prepare();
        
        $hasEvery = new HasEvery($stream, $values, $mode);
        $hasEvery->setNext(new Ending());
        
        //when
        $hasEvery->acceptSimpleData($dataSet, $signal, false);
        
        //then
        self::assertSame($expected, $hasEvery->get());
    }
    
    /**
     * @dataProvider getDataForTestHasEvery
     */
    public function test_HasEvery_acceptCollectedItems(int $mode, array $values, array $dataSet, bool $expected): void
    {
        //given
        [$stream, , $signal] = $this->prepare();
        
        $hasEvery = new HasEvery($stream, $values, $mode);
        $hasEvery->setNext(new Ending());
        
        //when
        $hasEvery->acceptCollectedItems($this->convertToItems($dataSet), $signal, false);
        
        //then
        self::assertSame($expected, $hasEvery->get());
    }
    
    public function getDataForTestHasEvery(): \Generator
    {
        $cases = [
            [[3, 5], false],
            [[3, 1], true],
        ];
        
        $dataSet = [1, 2, 3, 4];
        
        yield from $this->generateVariationsWithValues($cases, $dataSet);
    }
    
    public function test_limit_of_SortLimited_can_be_only_decreased(): void
    {
        $sortLimited = new SortLimited(5);
        self::assertSame(5, $sortLimited->limit());
        
        $sortLimited->applyLimit(3);
        self::assertSame(3, $sortLimited->limit());
        
        $sortLimited->applyLimit(7);
        self::assertSame(3, $sortLimited->limit());
        
        $sortLimited->applyLimit(1);
        self::assertSame(1, $sortLimited->limit());
        
        $sortLimited->applyLimit(2);
        self::assertSame(1, $sortLimited->limit());
    }
    
    public function test_SingeItem_strategy_is_protected_agains_change_its_limit(): void
    {
        $state = new SingleItem(new SortLimited(1), Check::VALUE, false, null);
        $state->setLength(1);
        
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('It is forbidden to change the length of SingleItem collector');
        
        $state->setLength(2);
    }
    
    public function test_Last_collectDataFromProducer_not_countable(): void
    {
        //given
        [$stream, , $signal] = $this->prepare();
        
        $last = new Last($stream);
        $last->setNext(new Ending());
        
        //when
        $last->collectDataFromProducer(Producers::tokenizer(' ', 'foo bar zoo'), $signal, false);
        
        //then
        self::assertSame('zoo', $last->get());
    }
    
    public function test_GroupBy_collectDataFromProducer_throws_exception_on_invalid_classifier(): void
    {
        //Assert
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Value returned from discriminator is inappropriate (got array)');
        
        //Arrange
        $groupBy = new GroupBy(static fn($v): array => [$v]);
        
        //Act
        $groupBy->collectDataFromProducer(Producers::from([1]), new Signal(Stream::empty()), false);
    }
    
    public function test_GroupBy_acceptSimpleData_throws_exception_on_invalid_classifier(): void
    {
        //Assert
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Value returned from discriminator is inappropriate (got array)');
        
        //Arrange
        $groupBy = new GroupBy(static fn($v): array => [$v]);
        
        //Act
        $groupBy->acceptSimpleData([1], new Signal(Stream::empty()), false);
    }
    
    public function test_GroupBy_acceptCollectedItems_throws_exception_on_invalid_classifier(): void
    {
        //Assert
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Value returned from discriminator is inappropriate (got array)');
        
        //Arrange
        $groupBy = new GroupBy(static fn($v): array => [$v]);
        
        //Act
        $groupBy->acceptCollectedItems([new Item(1, 2)], new Signal(Stream::empty()), false);
    }
    
    public function test_Dispatch_throws_excpetion_when_param_handlers_is_empty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Param handlers cannot be empty');
        
        new Dispatch('is_string', []);
    }
    
    public function test_Dispatch_throws_exception_when_there_is_no_handler_defined_for_classifier(): void
    {
        //Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('There is no handler defined for classifier 1');
        
        //Arrange
        $signal = new Signal(Stream::empty());
        $signal->item->key = 1;
        $signal->item->value = 'foo';
        
        $dispatch = new Dispatch('is_string', ['yes' => Consumers::counter()]);
        
        //Act
        $dispatch->handle($signal);
    }
    
    public function test_Dispatch_throws_exception_when_classifier_is_invalid(): void
    {
        //Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Value returned from discriminator is inappropriate (got double)');
        
        //Arrange
        $signal = new Signal(Stream::empty());
        $signal->item->key = 1;
        $signal->item->value = 'foo';
        
        $dispatch = new Dispatch(
            static fn($v, $k): float => 15.5,
            ['yes' => Consumers::counter()]
        );
        
        //Act
        $dispatch->handle($signal);
    }
    
    public function test_Dispatcher_handlers_factory_throws_exception_when_not_StreamPipe_is_provided(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Only StrimPipe is supported as Handler for Dispatcher');
        
        Handlers::getAdapter(ResultItem::createNotFound());
    }
    
    public function test_Dispatcher_handlers_factory_throws_exception_when_handler_is_unsupported(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param handler - it cannot be string');
        
        Handlers::getAdapter('wrong');
    }
    
    public function test_Classify_throws_exception_when_Discriminator_returns_invalid_value(): void
    {
        //Assert
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unsupported value was returned from discriminator (got array)');
        
        //Arrange
        $signal = new Signal(Stream::empty());
        $classify = new Classify(static fn($v): array => [$v]);
        
        //Act
        $classify->handle($signal);
    }
    
    public function test_StoreIn_throws_exception_when_param_buffer_is_invalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param buffer');
        
        $object = new \stdClass();
        
        new StoreIn($object);
    }
    
    public function test_Segregate_can_handle_limit_zero_properly(): void
    {
        $segregate = new Segregate(3);
        
        self::assertFalse($segregate->applyLimit(0));
        self::assertSame(1, $segregate->limit());
    }
    
    public function test_Ending_can_return_previous_operation(): void
    {
        //given
        $limit = new Limit(1);
        $ending = new Ending();
        
        $limit->setNext($ending, true);
        
        //when
        $prev = $ending->getPrev();
        
        //then
        self::assertSame($limit, $prev);
    }
    
    public function test_UnpackTyple_throws_exception_when_element_is_not_a_tuple(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('UnpackTuple cannot handle value which is not a valid tuple');
        
        Stream::from(['a', 'b'])->unpackTuple()->run();
    }
    
    public function test_UnpackTuple_can_handle_numerical_array(): void
    {
        $data = ['a' => 1, 'b' => 2];
        
        $result = Stream::from($data)
            ->makeTuple()
            ->limit(5)
            ->unpackTuple()
            ->toArrayAssoc();
        
        self::assertSame($data, $result);
    }
    
    public function test_UnpackTuple_can_handle_assoc_array(): void
    {
        $data = ['a' => 1, 'b' => 2];
        
        $result = Stream::from($data)
            ->makeTuple(true)
            ->limit(5)
            ->unpackTuple(true)
            ->toArrayAssoc();
        
        self::assertSame($data, $result);
    }
    
    /**
     * @return Item[]
     */
    private function convertToItems(array $data): array
    {
        $items = [];
        
        foreach ($data as $key => $value) {
            $items[] = new Item($key, $value);
        }
        
        return $items;
    }
    
    private function generateVariationsWithValues(array $cases, $dataSet): \Generator
    {
        foreach ([Check::VALUE, Check::KEY, Check::ANY, Check::BOTH] as $mode) {
            foreach ($cases as [$values, $expected]) {
                yield [$mode, $values, $dataSet, $expected];
            }
        }
    }
    
    private function sendToPipe(array $data, Pipe $pipe, Signal $signal): void
    {
        $pipe->prepare();
        
        $item = $signal->item;
        foreach ($data as $item->key => $item->value) {
            $pipe->head->handle($signal);
            
            if ($signal->isEmpty || !$signal->isWorking) {
                break;
            }
        }
        
        $pipe->head->streamingFinished($signal);
    }
    
    private function addToPipe(Pipe $pipe, Operation ...$operations): void
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