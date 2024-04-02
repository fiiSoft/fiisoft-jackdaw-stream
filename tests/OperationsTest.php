<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Comparator\Comparators;
use FiiSoft\Jackdaw\Consumer\Consumers;
use FiiSoft\Jackdaw\Exception\ImpossibleSituationException;
use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Pipe;
use FiiSoft\Jackdaw\Internal\ResultItem;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Mapper\Mappers;
use FiiSoft\Jackdaw\Operation\Collecting\Segregate;
use FiiSoft\Jackdaw\Operation\Collecting\SortLimited;
use FiiSoft\Jackdaw\Operation\Collecting\Tail;
use FiiSoft\Jackdaw\Operation\Exception\OperationExceptionFactory;
use FiiSoft\Jackdaw\Operation\Filtering\EveryNth;
use FiiSoft\Jackdaw\Operation\Filtering\FilterBy;
use FiiSoft\Jackdaw\Operation\Internal\Pipe\Ending;
use FiiSoft\Jackdaw\Operation\Internal\Pipe\Initial;
use FiiSoft\Jackdaw\Operation\Mapping\Aggregate;
use FiiSoft\Jackdaw\Operation\Mapping\Chunk;
use FiiSoft\Jackdaw\Operation\Mapping\Flat;
use FiiSoft\Jackdaw\Operation\Mapping\MapFieldWhen;
use FiiSoft\Jackdaw\Operation\Mapping\MapKey;
use FiiSoft\Jackdaw\Operation\Mapping\MapKeyValue;
use FiiSoft\Jackdaw\Operation\Operation;
use FiiSoft\Jackdaw\Operation\Sending\Dispatch;
use FiiSoft\Jackdaw\Operation\Sending\Dispatcher\Handlers;
use FiiSoft\Jackdaw\Operation\Sending\FeedMany;
use FiiSoft\Jackdaw\Operation\Sending\SendTo;
use FiiSoft\Jackdaw\Operation\Sending\SendToMax;
use FiiSoft\Jackdaw\Operation\Sending\StoreIn;
use FiiSoft\Jackdaw\Operation\Special\Iterate;
use FiiSoft\Jackdaw\Operation\Special\Limit;
use FiiSoft\Jackdaw\Operation\Terminating\Collect;
use FiiSoft\Jackdaw\Operation\Terminating\CollectKeys;
use FiiSoft\Jackdaw\Operation\Terminating\Count;
use FiiSoft\Jackdaw\Operation\Terminating\Find;
use FiiSoft\Jackdaw\Operation\Terminating\First;
use FiiSoft\Jackdaw\Operation\Terminating\Has;
use FiiSoft\Jackdaw\Operation\Terminating\HasEvery;
use FiiSoft\Jackdaw\Operation\Terminating\Reduce;
use FiiSoft\Jackdaw\Reducer\Reducers;
use FiiSoft\Jackdaw\Stream;
use PHPUnit\Framework\TestCase;

final class OperationsTest extends TestCase
{
    public function test_Tail_throws_exception_when_limit_is_invalid(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('length'));
        
        new Tail(0);
    }
    
    public function test_SendToMax_throws_exception_when_limit_is_invalid(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('times'));
        
        new SendToMax(0, Consumers::counter());
    }
    
    /**
     * @dataProvider getDataForTestAggregateThrowsExceptionWhenParamKeysIsInvalid
     */
    public function test_Aggregate_throws_exception_when_param_keys_is_invalid($keys): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('keys'));
        
        Aggregate::create($keys);
    }
    
    public static function getDataForTestAggregateThrowsExceptionWhenParamKeysIsInvalid(): array
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
        $this->expectExceptionObject(InvalidParamException::byName('field'));
        
        new MapFieldWhen('', 'is_string', 'strtolower');
    }
    
    public function test_SortLimited_throws_exception_when_param_limit_is_invalid(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('limit'));
        
        SortLimited::create(0);
    }
    
    public function test_Ending_operation(): void
    {
        $operation = new Ending();
        
        self::assertFalse($operation->streamingFinished(new Signal(Stream::empty())));
    }
    
    public function test_Ending_operation_cannot_be_removed_from_chain(): void
    {
        $operation = new Ending();
        $this->expectExceptionObject(ImpossibleSituationException::called('removeFromChain', $operation));
        
        $operation->removeFromChain();
    }
    
    public function test_Ending_operation_has_to_be_the_last_operation_in_chain(): void
    {
        $operation = new Ending();
        $this->expectExceptionObject(ImpossibleSituationException::called('setNext', $operation));
    
        $operation->setNext(new Ending());
    }
    
    public function test_Ending_cannot_prepend_other_operation(): void
    {
        $operation = new Ending();
        $this->expectExceptionObject(ImpossibleSituationException::called('prepend', $operation));
        
        $operation->prepend(new Ending());
    }
    
    public function test_Initial_operation_have_to_be_first_operation_in_chain(): void
    {
        $operation = new Initial();
        $this->expectExceptionObject(ImpossibleSituationException::called('setPrev', $operation));
    
        $operation->setPrev(new Ending());
    }
    
    public function test_Initial_cannot_prepend_other_operation(): void
    {
        $operation = new Initial();
        $this->expectExceptionObject(ImpossibleSituationException::called('prepend', $operation));
    
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
        $chunk = Chunk::create(5, true);
        
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
    
    public function test_Initial_buildStream_returns_passed_stream(): void
    {
        $initial = new Initial();
        
        $stream = new \ArrayIterator([]);
        $actual = $initial->buildStream($stream);
        
        self::assertSame($stream, $actual);
    }
    
    public function test_Iterate_buildStream_returns_passed_stream(): void
    {
        $initial = new Iterate();
        
        $stream = new \ArrayIterator([]);
        $actual = $initial->buildStream($stream);
        
        self::assertSame($stream, $actual);
    }
    
    public function test_FeedMany_throws_exception_when_no_streams_are_provided(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('streams'));
        
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
        $this->expectExceptionObject(OperationExceptionFactory::invalidKeyValueMapper(3));
        
        MapKeyValue::create(static fn($a, $b, $c): bool => true);
    }
    
    /**
     * @dataProvider getDataForTestMapKeyValueThrowsExceptionWhenDeclaredTypeOfValueOfMapperIsNotArray
     */
    public function test_MapKeyValue_throws_exception_when_return_type_of_mapper_is_not_array(callable $mapper): void
    {
        $this->expectExceptionObject(OperationExceptionFactory::wrongTypeOfKeyValueMapper());
        
        MapKeyValue::create($mapper);
    }
    
    public static function getDataForTestMapKeyValueThrowsExceptionWhenDeclaredTypeOfValueOfMapperIsNotArray(): \Generator
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
    
    /**
     * @dataProvider getDataForCollect
     */
    public function test_Collect_basic(bool $reindex, array $dataSet, array $expected): void
    {
        [$stream, $pipe, $signal] = $this->prepare();
        
        $collect = Collect::create($stream, $reindex);
        $this->addToPipe($pipe, $collect);
        
        $this->sendToPipe($dataSet, $pipe, $signal);
        self::assertSame($expected, $collect->get());
    }
    
    public static function getDataForCollect(): array
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
    
    public static function getDataForCollectKeys(): array
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
    
    public static function getDataForCount(): array
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
    
    public static function getDataForTestHas(): \Generator
    {
        $cases = [
            [Filters::greaterOrEqual(5), false],
            [3, true],
        ];
        
        $dataSet = [1, 2, 3 => 3, 4];
        
        yield from self::generateVariationsWithValues($cases, $dataSet);
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
    
    private function assertFindResultIsCorrect(Find $find, ?array $expected): void
    {
        self::assertSame($expected !== null, $find->hasResult());
        
        if ($expected !== null) {
            self::assertSame($expected[0], $find->key());
            self::assertSame($expected[1], $find->get());
        }
    }
    
    public static function getDataForTestFind(): array
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
    
    public static function getDataForTestReduce(): array
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
        
        $collect = Collect::create($stream);
        $this->addToPipe($pipe, new Tail(2), $collect);
        
        $this->sendToPipe(['a', 'b', 'c', 'd'], $pipe, $signal);
        self::assertSame([2 => 'c', 'd'], $collect->get());
    }
    
    public static function getDataForTestSort(): array
    {
        $expected2 = [6 => 1, 8 => 2, 5 => 2, 7 => 3, 1 => 3, 4 => 5, 3 => 5, 2 => 6, 0 => 6];
        $expected3 = [0 => 6, 1 => 3, 2 => 6, 3 => 5, 4 => 5, 5 => 2, 6 => 1, 7 => 3, 8 => 2];
        $expected4 = [8 => 2, 7 => 3, 6 => 1, 5 => 2, 4 => 5, 3 => 5, 2 => 6, 1 => 3, 0 => 6];
        $expected5 = [6 => 1, 5 => 2, 8 => 2, 1 => 3, 7 => 3, 3 => 5, 4 => 5, 0 => 6, 2 => 6];
        $expected6 = [2 => 6, 0 => 6, 4 => 5, 3 => 5, 7 => 3, 1 => 3, 8 => 2, 5 => 2, 6 => 1];
        
        //the exact order of elements sorted by native functions depends on PHP version
        if (\PHP_MAJOR_VERSION === 7) {
            $expected1 = [0 => 6, 2 => 6, 4 => 5, 3 => 5, 7 => 3, 1 => 3, 8 => 2, 5 => 2, 6 => 1];
        } else {
            $expected1 = $expected6;
        }
        
        //reversed, mode, Comparator, expected
        return [
            0 => [
                false, Check::VALUE, null, $expected2
            ],
            1 => [
                true, Check::VALUE, null, $expected1
            ],
            2 => [
                false, Check::VALUE, Comparators::default(), $expected2
            ],
            3 => [
                true, Check::VALUE, Comparators::default(), $expected1
            ],
            4 => [
                false, Check::KEY, null, $expected3
            ],
            5 => [
                true, Check::KEY, null, $expected4
            ],
            6 => [
                false, Check::KEY, Comparators::default(), $expected3
            ],
            7 => [
                true, Check::KEY, Comparators::default(), $expected4
            ],
            8 => [
                false, Check::BOTH, null, $expected5
            ],
            9 => [
                true, Check::BOTH, null, $expected6
            ],
            10 => [
                false, Check::BOTH, Comparators::default(), $expected5
            ],
            11 => [
                true, Check::BOTH, Comparators::default(), $expected6
            ],
        ];
    }
    
    public static function getDataForTestReverseAcceptSimpleData(): array
    {
        //data, expected
        return [
            [['a', 'b', 'c'], [2 => 'c', 1 => 'b', 0 => 'a']],
            [[], []],
        ];
    }
    
    /**
     * @dataProvider getDataForTestHasEvery
     */
    public function test_HasEvery_basic(int $mode, array $values, array $dataSet, bool $expected): void
    {
        [$stream, $pipe, $signal] = $this->prepare();
        
        $hasEvery = HasEvery::create($stream, $values, $mode);
        $this->addToPipe($pipe, $hasEvery);
        
        $this->sendToPipe($dataSet, $pipe, $signal);
        self::assertSame($expected, $hasEvery->get());
    }
    
    public static function getDataForTestHasEvery(): \Generator
    {
        $cases = [
            [[3, 5], false],
            [[3, 1], true],
        ];
        
        $dataSet = [1, 2, 3, 4];
        
        yield from self::generateVariationsWithValues($cases, $dataSet);
    }
    
    public function test_changing_limit_of_SortLimited(): void
    {
        $sortLimited = SortLimited::create(5);
        self::assertSame(5, $sortLimited->limit());
        
        self::assertTrue($sortLimited->applyLimit(3));
        self::assertSame(3, $sortLimited->limit());
        
        self::assertTrue($sortLimited->applyLimit(7));
        self::assertSame(3, $sortLimited->limit());
        
        self::assertFalse($sortLimited->applyLimit(1));
        
        $sortLimited = $sortLimited->createWithLimit(1);
        self::assertSame(1, $sortLimited->limit());
        
        self::assertTrue($sortLimited->applyLimit(1));
        self::assertFalse($sortLimited->applyLimit(2));
        
        $sortLimited = $sortLimited->createWithLimit(2);
        self::assertSame(2, $sortLimited->limit());
    }
    
    public function test_Dispatch_throws_excpetion_when_param_handlers_is_empty(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('handlers'));
        
        new Dispatch('is_string', []);
    }
    
    public function test_Dispatch_throws_exception_when_there_is_no_handler_defined_for_classifier(): void
    {
        //Arrange
        $signal = new Signal(Stream::empty());
        $signal->item->key = 1;
        $signal->item->value = 'foo';
        
        $dispatch = new Dispatch('is_string', ['yes' => Consumers::counter()]);
        
        //Assert
        $this->expectExceptionObject(OperationExceptionFactory::handlerIsNotDefined($signal->item->key));
        
        //Act
        $dispatch->handle($signal);
    }
    
    public function test_Dispatch_throws_exception_when_classifier_is_invalid(): void
    {
        //Assert
        $this->expectExceptionObject(OperationExceptionFactory::handlerIsNotDefined('ohno'));
        
        //Arrange
        $signal = new Signal(Stream::empty());
        $signal->item->key = 1;
        $signal->item->value = 'foo';
        
        $dispatch = new Dispatch(
            static fn($v, $k): string => 'ohno',
            ['yes' => Consumers::counter()]
        );
        
        //Act
        $dispatch->handle($signal);
    }
    
    public function test_Dispatcher_handlers_factory_throws_exception_when_not_StreamPipe_is_provided(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('handler'));
        
        Handlers::getAdapter(ResultItem::createNotFound());
    }
    
    public function test_StoreIn_throws_exception_when_param_buffer_is_invalid(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('buffer'));
        
        $object = new \stdClass();
        
        StoreIn::create($object);
    }
    
    public function test_Segregate_can_handle_limit_zero_properly(): void
    {
        $segregate = new Segregate(3);
        
        self::assertFalse($segregate->applyLimit(0));
        
        $segregate = $segregate->createWithLimit(0);
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
    
    public function test_EveryNth_throws_exception_when_param_num_is_invalid(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('num'));
        
        new EveryNth(0);
    }
    
    public function test_FilterBy_throws_exception_when_param_field_is_invalid(): void
    {
        $field = ['a'];
        
        $this->expectExceptionObject(InvalidParamException::describe('field', $field));
        
        new FilterBy($field, 'is_int');
    }
    
    public function test_Limit_can_change_its_limit_and_create_new_Limit(): void
    {
        $limit = new Limit(8);
        self::assertSame(8, $limit->limit());
        
        self::assertTrue($limit->applyLimit(3));
        self::assertSame(3, $limit->limit());
        
        $limit = $limit->createWithLimit(5);
        self::assertSame(5, $limit->limit());
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
    
    private static function generateVariationsWithValues(array $cases, $dataSet): \Generator
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
        return $this->getPropertyFromStream($stream, 'pipe');
    }
    
    private function getSignalFromStream(Stream $stream): Signal
    {
        return $this->getPropertyFromStream($stream, 'signal');
    }
    
    private function getPropertyFromStream(Stream $stream, string $property)
    {
        $method = (new \ReflectionObject($stream))->getMethod('initialize');
        $method->setAccessible(true);
        $method->invoke($stream);
        
        $prop = (new \ReflectionObject($stream))->getProperty($property);
        $prop->setAccessible(true);
        
        return $prop->getValue($stream);
    }
}