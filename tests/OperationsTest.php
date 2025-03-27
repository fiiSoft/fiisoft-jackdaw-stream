<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Comparator\Basic\GenericComparator;
use FiiSoft\Jackdaw\Comparator\Comparators;
use FiiSoft\Jackdaw\Consumer\Consumers;
use FiiSoft\Jackdaw\Exception\ImpossibleSituationException;
use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Filter\IdleFilter;
use FiiSoft\Jackdaw\Filter\Logic\OpAND\Optim\TwoArgsAND;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Pipe;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Mapper\Mappers;
use FiiSoft\Jackdaw\Memo\Inspector\SequenceIsFull;
use FiiSoft\Jackdaw\Memo\Memo;
use FiiSoft\Jackdaw\Operation\Exception\OperationExceptionFactory;
use FiiSoft\Jackdaw\Operation\Filtering\FilterByMany;
use FiiSoft\Jackdaw\Operation\Filtering\FilterMany;
use FiiSoft\Jackdaw\Operation\Filtering\StackableFilter;
use FiiSoft\Jackdaw\Operation\Filtering\StackableFilterBy;
use FiiSoft\Jackdaw\Operation\Filtering\Unique\ItemByItemChecker\FullAssocChecker;
use FiiSoft\Jackdaw\Operation\Internal\DispatchReady;
use FiiSoft\Jackdaw\Operation\Internal\Operations as OP;
use FiiSoft\Jackdaw\Operation\Internal\Pipe\Ending;
use FiiSoft\Jackdaw\Operation\Internal\Pipe\Initial;
use FiiSoft\Jackdaw\Operation\Operation;
use FiiSoft\Jackdaw\Operation\Sending\Dispatcher\Handlers;
use FiiSoft\Jackdaw\Operation\Special\Iterate;
use FiiSoft\Jackdaw\Reducer\Reducers;
use FiiSoft\Jackdaw\Stream;
use FiiSoft\Jackdaw\ValueRef\Exception\WrongIntValueException;
use FiiSoft\Jackdaw\ValueRef\IntNum;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class OperationsTest extends TestCase
{
    public function test_Tail_throws_exception_when_limit_is_invalid(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('length'));
        
        OP::tail(0);
    }
    
    public function test_SendToMax_throws_exception_when_limit_is_invalid(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('times'));
     
        OP::callMax(0, Consumers::counter());
    }
    
    /**
     * @dataProvider getDataForTestAggregateThrowsExceptionWhenParamKeysIsInvalid
     */
    #[DataProvider('getDataForTestAggregateThrowsExceptionWhenParamKeysIsInvalid')]
    public function test_Aggregate_throws_exception_when_param_keys_is_invalid($keys): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('keys'));
        
        OP::aggregate($keys);
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
        $flat = OP::flat();
        $maxLevel = $flat->maxLevel();
        
        $flat->mergeWith(OP::flat());
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
        
        OP::mapFieldWhen('', 'is_string', 'strtolower');
    }
    
    public function test_SortLimited_throws_exception_when_param_limit_is_invalid(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('limit'));
        
        OP::sortLimited(0);
    }
    
    public function test_Ending_operation(): void
    {
        $operation = $this->endingOperation();
        
        self::assertFalse($operation->streamingFinished(Signal::shared()));
    }
    
    public function test_Ending_operation_cannot_be_removed_from_chain(): void
    {
        $operation = $this->endingOperation();
        $this->expectExceptionObject(ImpossibleSituationException::called('removeFromChain', $operation));
        
        $operation->removeFromChain();
    }
    
    public function test_Ending_operation_has_to_be_the_last_operation_in_chain(): void
    {
        $operation = $this->endingOperation();
        $this->expectExceptionObject(ImpossibleSituationException::called('setNext', $operation));
    
        $operation->setNext($this->endingOperation());
    }
    
    public function test_Ending_cannot_prepend_other_operation(): void
    {
        $operation = $this->endingOperation();
        $this->expectExceptionObject(ImpossibleSituationException::called('prepend', $operation));
        
        $operation->prepend($this->endingOperation());
    }
    
    public function test_Initial_operation_have_to_be_first_operation_in_chain(): void
    {
        $operation = new Initial();
        $this->expectExceptionObject(ImpossibleSituationException::called('setPrev', $operation));
    
        $operation->setPrev($this->endingOperation());
    }
    
    public function test_Initial_operation_getPrev_always_returns_null(): void
    {
        $operation = new Initial();
        self::assertNull($operation->getPrev());
        
        $operation->setNext(OP::limit(1));
        self::assertNull($operation->getPrev());
    }
    
    public function test_Initial_cannot_prepend_other_operation(): void
    {
        $operation = new Initial();
        $this->expectExceptionObject(ImpossibleSituationException::called('prepend', $operation));
    
        $operation->prepend($this->endingOperation());
    }
    
    public function test_Initial_operation_passes_signal_to_next_operation(): void
    {
        //given
        $flag = false;
        $initial = new Initial();
        
        $initial->setNext(OP::call(static function () use (&$flag): void {
            $flag = true;
        }));
        
        $signal = Signal::shared();
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
        $chunk = OP::chunk(5, true);
        
        $initial->setNext($chunk);
        $chunk->setNext(OP::call(static function ($value, $key) use (&$passedData): void {
            $passedData[$key] = $value;
        }));
        
        $signal = Signal::shared();
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
        
        OP::feed();
    }
    
    public function test_MapKey_can_merge_Value_with_FieldValue(): void
    {
        $first = OP::mapKey(Mappers::value());
        $second = OP::mapKey(Mappers::fieldValue('foo'));
        
        self::assertTrue($first->mergeWith($second));
        self::assertFalse($first->mergeWith(OP::mapKey(Mappers::simple('bar'))));
    }
    
    public function test_MapKey_can_merge_FieldValue_with_Value(): void
    {
        $first = OP::mapKey(Mappers::fieldValue('foo'));
        $second = OP::mapKey(Mappers::value());
        
        self::assertTrue($first->mergeWith($second));
    }
    
    public function test_MapKeyValue_throws_exception_when_number_of_arguments_accepted_by_mapper_is_invalid(): void
    {
        $this->expectExceptionObject(OperationExceptionFactory::invalidKeyValueMapper(3));
        
        OP::mapKeyValue(static fn($a, $b, $c): bool => true);
    }
    
    /**
     * @dataProvider getDataForTestMapKeyValueThrowsExceptionWhenDeclaredTypeOfValueOfMapperIsNotArray
     */
    #[DataProvider('getDataForTestMapKeyValueThrowsExceptionWhenDeclaredTypeOfValueOfMapperIsNotArray')]
    public function test_MapKeyValue_throws_exception_when_return_type_of_mapper_is_not_array(callable $mapper): void
    {
        $this->expectExceptionObject(OperationExceptionFactory::wrongTypeOfKeyValueMapper());
        
        OP::mapKeyValue($mapper);
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
        
        $first = OP::first($stream);
        $this->addToPipe($pipe, $first);
        
        $this->sendToPipe([8, 3, 5], $pipe, $signal);
        self::assertSame(8, $first->get());
    }
    
    /**
     * @dataProvider getDataForCollect
     */
    #[DataProvider('getDataForCollect')]
    public function test_Collect_basic(bool $reindex, array $dataSet, array $expected): void
    {
        [$stream, $pipe, $signal] = $this->prepare();
        
        $collect = OP::collect($stream, $reindex);
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
    #[DataProvider('getDataForCollectKeys')]
    public function test_CollectKeys_basic(array $dataSet, array $expected): void
    {
        [$stream, $pipe, $signal] = $this->prepare();
        
        $collectKeys = OP::collectKeys($stream);
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
    #[DataProvider('getDataForCount')]
    public function test_Count_basic(array $dataSet, int $expected): void
    {
        [$stream, $pipe, $signal] = $this->prepare();
        
        $count = OP::count($stream);
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
    #[DataProvider('getDataForTestHas')]
    public function test_Has_basic(int $mode, $predicate, array $dataSet, bool $expected): void
    {
        [$stream, $pipe, $signal] = $this->prepare();
        
        $has = OP::has($stream, $predicate, $mode);
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
    #[DataProvider('getDataForTestFind')]
    public function test_Find_basic(int $mode, $predicate, array $dataSet, ?array $expected): void
    {
        //given
        [$stream, $pipe, $signal] = $this->prepare();
        
        $find = OP::find($stream, $predicate, $mode);
        $this->addToPipe($pipe, $find);
        
        //when
        $this->sendToPipe($dataSet, $pipe, $signal);
        
        //then
        self::assertSame($expected !== null, $find->found());
        
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
    #[DataProvider('getDataForTestReduce')]
    public function test_Reduce_basic(array $dataSet, ?string $expected): void
    {
        [$stream, $pipe, $signal] = $this->prepare();
        
        $reduce = OP::reduce($stream, Reducers::concat());
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
        
        $collect = OP::collect($stream);
        $this->addToPipe($pipe, OP::tail(2), $collect);
        
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
    #[DataProvider('getDataForTestHasEvery')]
    public function test_HasEvery_basic(int $mode, array $values, array $dataSet, bool $expected): void
    {
        [$stream, $pipe, $signal] = $this->prepare();
        
        $hasEvery = OP::hasEvery($stream, $values, $mode);
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
        $sortLimited = OP::sortLimited(5);
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
        
        OP::dispatch('is_string', []);
    }
    
    public function test_Dispatch_throws_exception_when_there_is_no_handler_defined_for_classifier(): void
    {
        //Arrange
        $signal = Signal::shared();
        $signal->item->key = 1;
        $signal->item->value = 'foo';
        
        $dispatch = OP::dispatch('is_string', ['yes' => Consumers::counter()]);
        
        //Assert
        $this->expectExceptionObject(OperationExceptionFactory::handlerIsNotDefined(true));
        
        //Act
        $dispatch->handle($signal);
    }
    
    public function test_Dispatch_throws_exception_when_classifier_is_invalid(): void
    {
        //Assert
        $this->expectExceptionObject(OperationExceptionFactory::handlerIsNotDefined('ohno'));
        
        //Arrange
        $signal = Signal::shared();
        $signal->item->key = 1;
        $signal->item->value = 'foo';
        
        $dispatch = OP::dispatch(
            static fn($v, $k): string => 'ohno',
            ['yes' => Consumers::counter()]
        );
        
        //Act
        $dispatch->handle($signal);
    }
    
    public function test_Dispatcher_handlers_factory_throws_exception_on_unsupported_object(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('handler'));

        Handlers::getAdapter(new class implements DispatchReady {});
    }
    
    public function test_StoreIn_throws_exception_when_param_buffer_is_invalid(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('buffer'));
        
        $this->createStoreInWith(new \stdClass());
    }
    
    private function createStoreInWith($object): void
    {
        OP::storeIn($object);
    }
    
    public function test_Segregate_can_handle_limit_zero_properly(): void
    {
        $segregate = OP::segregate(3);
        
        self::assertFalse($segregate->applyLimit(0));
        
        $segregate = $segregate->createWithLimit(0);
        self::assertSame(1, $segregate->limit());
    }
    
    public function test_Ending_can_return_previous_operation(): void
    {
        //given
        $limit = OP::limit(1);
        $ending = $this->endingOperation();
        
        $limit->setNext($ending, true);
        
        //when
        $prev = $ending->getPrev();
        
        //then
        self::assertSame($limit, $prev);
    }
    
    private function endingOperation(): Ending
    {
        /* @var $ending Ending */
        $ending = (new Initial())->getNext();
        
        return $ending;
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
        
        OP::everyNth(0);
    }
    
    public function test_SkipNth_throws_exception_when_param_num_is_invalid(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('num'));
        
        OP::skipNth(1);
    }
    
    public function test_FilterBy_throws_exception_when_param_field_is_invalid(): void
    {
        $field = ['a'];
        
        $this->expectExceptionObject(InvalidParamException::describe('field', $field));
        
        OP::filterBy($field, 'is_int');
    }
    
    public function test_Limit_can_change_its_limit_and_create_new_Limit(): void
    {
        $limit = OP::limit(8);
        self::assertSame(8, $limit->limit());
        
        self::assertTrue($limit->applyLimit(3));
        self::assertSame(3, $limit->limit());
        
        $limit = $limit->createWithLimit(5);
        self::assertSame(5, $limit->limit());
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
    
    /**
     * @return mixed
     */
    private function getPropertyFromStream(Stream $stream, string $property)
    {
        $method = (new \ReflectionObject($stream))->getMethod('initialize');
        $method->setAccessible(true);
        $method->invoke($stream);
        
        $prop = (new \ReflectionObject($stream))->getProperty($property);
        $prop->setAccessible(true);
        
        return $prop->getValue($stream);
    }
    
    public function test_FullAssocChecker_throws_exception_when_Comparator_is_invalid(): void
    {
        //Assert
        $this->expectExceptionObject(OperationExceptionFactory::invalidComparator());
        
        //Arrange
        $comparator = Comparators::getAdapter('is_string');
        self::assertInstanceOf(GenericComparator::class, $comparator);
        
        //Act
        new FullAssocChecker($comparator);
    }
    
    public function test_ReadMany_throws_exception_when_number_of_repetitions_is_invalid(): void
    {
        $provider = IntNum::constant(-1);
        
        $this->expectExceptionObject(WrongIntValueException::invalidNumber($provider));
        
        OP::readMany($provider);
    }
    
    public function test_ReadNext_throws_exception_when_number_of_repetitions_is_invalid(): void
    {
        $provider = IntNum::constant(-1);
        
        $this->expectExceptionObject(WrongIntValueException::invalidNumber($provider));
        
        OP::readNext($provider);
    }
    
    public function test_mapBy_throws_exception_when_param_mappers_is_empty(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('mappers'));
        
        OP::mapBy(['a', 'b'], []);
    }
    
    public function test_FilterMany_merge_checks_1(): void
    {
        //given
        $sequence = Memo::sequence();
        $predicate1 = $sequence->matches(['a', 'b']);
        $predicate2 = $sequence->matches(['d', 'c']);
        
        $op1 = OP::filter($predicate1);
        \assert($op1 instanceof StackableFilter);
        
        $op2 = OP::filter($predicate2);
        \assert($op2 instanceof StackableFilter);
        
        //when
        $filter = new FilterMany($op1, $op2);
        
        //then
        self::assertCount(1, $filter->getChecks());
        
        $check = $filter->getChecks()[0];
        self::assertNull($check->condition);
        self::assertFalse($check->negation);
        self::assertTrue($check->filter->equals(Filters::AND($predicate1, $predicate2)));
    }
    
    public function test_FilterMany_merge_checks_2(): void
    {
        //given
        $sequence = Memo::sequence();
        
        $op1 = OP::filter($sequence->matches(['a', 'b']));
        \assert($op1 instanceof StackableFilter);
        
        $op2 = OP::filter($sequence->matches(['a', 'b']));
        \assert($op2 instanceof StackableFilter);
        
        $op3 = OP::filter($sequence->matches(['a', 'b', 'c']));
        \assert($op3 instanceof StackableFilter);
        
        //when
        $filter = new FilterMany($op1, $op2);
        $filter->add($op3);
        
        //then
        $checks = $filter->getChecks();
        self::assertCount(1, $checks);
        
        $check = $checks[0];
        self::assertNull($check->condition);
        self::assertFalse($check->negation);
        
        \assert($check->filter instanceof TwoArgsAND);
        [$first, $second] = $check->filter->getFilters();
        
        self::assertTrue($first->equals(Filters::getAdapter($sequence->matches(['a', 'b']))));
        self::assertTrue($second->equals(Filters::getAdapter($sequence->matches(['a', 'b', 'c']))));
    }
    
    public function test_FilterMany_merge_checks_3(): void
    {
        //given
        $sequence = Memo::sequence();
        
        $op1 = OP::filter($sequence->inspect(new SequenceIsFull()));
        \assert($op1 instanceof StackableFilter);
        
        $op2 = OP::omit($sequence->inspect(new SequenceIsFull()));
        \assert($op2 instanceof StackableFilter);
        
        //when
        $filter = new FilterMany($op1, $op2);
        
        //then
        self::assertCount(1, $filter->getChecks());
        
        $check = $filter->getChecks()[0];
        
        self::assertNull($check->condition);
        self::assertFalse($check->negation);
        self::assertTrue($check->filter->equals(IdleFilter::false()));
    }
    
    public function test_FilterMany_merge_checks_4(): void
    {
        //given
        $sequence = Memo::sequence(2);
        $predicate = $sequence->matches(['b', 'c']);
        
        $op1 = OP::filter($predicate);
        \assert($op1 instanceof StackableFilter);
        
        $op2 = OP::filter(Filters::isInt());
        \assert($op2 instanceof StackableFilter);
        
        $op3 = OP::filterWhen($predicate, Filters::greaterThan(1));
        \assert($op3 instanceof StackableFilter);
        
        $op4 = OP::filterWhen($predicate, Filters::lessThan(5));
        \assert($op4 instanceof StackableFilter);
        
        //when
        $filter = new FilterMany($op1);
        $filter->add($op2);
        $filter->add($op3);
        $filter->add($op4);
        $checks = $filter->getChecks();
        
        //then
        self::assertCount(2, $checks);
        
        [$check1, $check2] = $checks;
        
        self::assertNull($check1->condition);
        self::assertFalse($check1->negation);
        self::assertTrue($check1->filter->equals(Filters::getAdapter($predicate)->and(Filters::isInt())));
        
        self::assertTrue($check2->condition->equals(Filters::getAdapter($predicate)));
        self::assertFalse($check2->negation);
        self::assertTrue($check2->filter->equals(Filters::greaterThan(1)->and(Filters::lessThan(5))));
    }
    
    public function test_FilterMany_merge_checks_5(): void
    {
        //given
        $op1 = OP::filter(Filters::isInt());
        \assert($op1 instanceof StackableFilter);
        
        //when
        $filter = new FilterMany($op1, $op1);
        $checks = $filter->getChecks();
        
        //then
        self::assertCount(1, $checks);
        
        $check = $checks[0];
        self::assertNull($check->condition);
        self::assertFalse($check->negation);
        self::assertTrue($check->filter->equals(Filters::isInt()));
    }
    
    public function test_FilterMany_merge_checks_6(): void
    {
        //given
        $op1 = OP::filter(Filters::isInt());
        \assert($op1 instanceof StackableFilter);
        
        $op2 = OP::omit(Filters::isInt());
        \assert($op2 instanceof StackableFilter);
        
        //when
        $filter = new FilterMany($op1, $op2);
        $checks = $filter->getChecks();
        
        //then
        self::assertCount(1, $checks);
        
        $check = $checks[0];
        self::assertNull($check->condition);
        self::assertFalse($check->negation);
        self::assertTrue($check->filter->equals(IdleFilter::false()));
    }
    
    public function test_FilterByMany_merge_checks_1(): void
    {
        //given
        $op1 = OP::filterBy('foo', Filters::isString());
        \assert($op1 instanceof StackableFilterBy);
        
        $op2 = OP::filterBy('bar', Filters::isInt());
        \assert($op2 instanceof StackableFilterBy);
        
        $op3 = OP::filterBy('foo', Filters::contains('zoo'));
        \assert($op3 instanceof StackableFilterBy);
        
        //when
        $filter = new FilterByMany($op1);
        $filter->add($op2);
        $filter->add($op3);
        
        $checks = $filter->getChecks();
        
        //then
        self::assertCount(2, $checks);
        
        [$check1, $check2] = $checks;
        
        self::assertFalse($check1->negation);
        self::assertSame('foo', $check1->field);
        self::assertTrue($check1->filter->equals(Filters::isString()->and(Filters::contains('zoo'))));
        
        self::assertFalse($check2->negation);
        self::assertSame('bar', $check2->field);
        self::assertTrue($check2->filter->equals(Filters::isInt()));
    }
    
    public function test_forkMatch_throws_exception_when_list_of_handlers_is_empty(): void
    {
        $this->expectExceptionObject(OperationExceptionFactory::forkMatchHandlersCannotBeEmpty());
        
        OP::forkMatch('is_string', []);
    }
}