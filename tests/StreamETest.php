<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Collector\Collectors;
use FiiSoft\Jackdaw\Comparator\Comparators;
use FiiSoft\Jackdaw\Comparator\Sorting\By;
use FiiSoft\Jackdaw\Comparator\Sorting\Key;
use FiiSoft\Jackdaw\Comparator\Sorting\Sorting;
use FiiSoft\Jackdaw\Comparator\Sorting\Value;
use FiiSoft\Jackdaw\Consumer\Consumers;
use FiiSoft\Jackdaw\Discriminator\Discriminators;
use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Exception\StreamExceptionFactory;
use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Handler\OnError;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Mapper\Exception\MapperExceptionFactory;
use FiiSoft\Jackdaw\Mapper\Mappers;
use FiiSoft\Jackdaw\Producer\Producer;
use FiiSoft\Jackdaw\Producer\Producers;
use FiiSoft\Jackdaw\Reducer\Reducers;
use FiiSoft\Jackdaw\Stream;
use FiiSoft\Jackdaw\ValueRef\Exception\WrongIntValueException;
use FiiSoft\Jackdaw\ValueRef\IntNum;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class StreamETest extends TestCase
{
    public function test_sort_by_value_and_key_with_callbacks(): void
    {
        $expected = [
            'e' => 4, 'h' => 4, 't' => 4, 'n' => 3, 'o' => 3, 'p' => 3, 'r' => 3, 'a' => 2, 'u' => 2, 'y' => 2
        ];
        
        $result = Stream::from(Stream::from($expected)->shuffle())
            ->sort(By::assoc(
                Comparators::multi(
                    static fn(int $v1, int $v2, string $k1, string $k2): int => $v2 <=> $v1,
                    static fn(int $v1, int $v2, string $k1, string $k2): int => $k1 <=> $k2,
                )
            ))
            ->toArrayAssoc();
        
        self::assertSame($expected, $result);
    }
    
    public function test_sort_by_valueDesc_keyAsc_by_dedicated_comparator(): void
    {
        $expected = [
            'e' => 4, 'h' => 4, 't' => 4, 'n' => 3, 'o' => 3, 'p' => 3, 'r' => 3, 'a' => 2, 'u' => 2, 'y' => 2
        ];
        
        self::assertSame(
            $expected,
            Stream::from(Stream::from($expected)->shuffle())->sort(By::both(Value::desc(), Key::asc()))->toArrayAssoc()
        );
    }
    
    public function test_sort_by_valueAsc_keyDesc_by_dedicated_comparator(): void
    {
        $expected = [
            'y' => 2, 'u' => 2, 'a' => 2, 'r' => 3, 'p' => 3, 'o' => 3, 'n' => 3, 't' => 4, 'h' => 4, 'e' => 4
        ];
        
        self::assertSame(
            $expected,
            Stream::from(Stream::from($expected)->shuffle())->sort(By::both(Value::asc(), Key::desc()))->toArrayAssoc()
        );
    }
    
    public function test_sort_by_valueAsc_keyAsc_by_default_comparator(): void
    {
        $expected = [
            'a' => 2, 'u' => 2, 'y' => 2, 'n' => 3, 'o' => 3, 'p' => 3, 'r' => 3, 'e' => 4, 'h' => 4, 't' => 4,
        ];
        
        self::assertSame(
            $expected,
            Stream::from(Stream::from($expected)->shuffle())->sort(By::assoc())->toArrayAssoc()
        );
    }
    
    public function test_sort_by_valueDesc_keyDesc_by_default_comparator(): void
    {
        $expected = [
            't' => 4, 'h' => 4, 'e' => 4, 'r' => 3, 'p' => 3, 'o' => 3, 'n' => 3, 'y' => 2, 'u' => 2, 'a' => 2,
        ];
        
        self::assertSame(
            $expected,
            Stream::from(Stream::from($expected)->shuffle())->rsort(By::assoc())->toArrayAssoc()
        );
    }
    
    public function test_rsort_with_valueAscKeyDesc_works_as_sort_with_valueDescKeyAsc(): void
    {
        $data = [
            'n' => 3, 'a' => 2, 'r' => 3, 'y' => 2, 'e' => 4, 'o' => 3, 't' => 4, 'p' => 3, 'h' => 4, 'u' => 2,
        ];
        
        self::assertSame(
            Stream::from($data)->rsort(By::both(Value::asc(), Key::desc()))->toArrayAssoc(),
            Stream::from($data)->sort(By::both(Value::desc(), Key::asc()))->toArrayAssoc()
        );
    }
    
    public function test_best_with_valueAscKeyDesc_works_as_worst_with_valueDescKeyAsc(): void
    {
        $data = [
            'n' => 3, 'a' => 2, 'r' => 3, 'y' => 2, 'e' => 4, 'o' => 3, 't' => 4, 'p' => 3, 'h' => 4, 'u' => 2,
        ];
        
        self::assertSame(
            Stream::from($data)->best(10, By::both(Value::asc(), Key::desc()))->toArrayAssoc(),
            Stream::from($data)->worst(10, By::both(Value::desc(), Key::asc()))->toArrayAssoc()
        );
    }
    
    public function test_findMax(): void
    {
        $data = [
            ['id' => 7, 'name' => 'Sue', 'age' => 17, 'sex' => 'female'],
            ['id' => 9, 'name' => 'Chris', 'age' => 26, 'sex' => 'male'],
            ['id' => 2, 'name' => 'Kate', 'age' => 35, 'sex' => 'female'],
            ['id' => 5, 'name' => 'Chris', 'age' => 20, 'sex' => 'male'],
            ['id' => 6, 'name' => 'Joanna', 'age' => 30, 'sex' => 'female'],
            ['id' => 10, 'name' => 'Tom', 'age' => 35, 'sex' => 'male'],
            ['id' => 1, 'name' => 'Sue', 'age' => 20, 'sex' => 'female'],
        ];
        
        $result = Stream::from($data)->findMax(3, Filters::filterBy('sex', 'female'))->toArrayAssoc();
        
        $expected = [
            0 => ['id' => 7, 'name' => 'Sue', 'age' => 17, 'sex' => 'female'],
            2 => ['id' => 2, 'name' => 'Kate', 'age' => 35, 'sex' => 'female'],
            4 => ['id' => 6, 'name' => 'Joanna', 'age' => 30, 'sex' => 'female'],
        ];
        
        self::assertSame($expected, $result);
    }
    
    public function test_filter_only_nonDecreasing_values(): void
    {
        self::assertSame(
            [3, 3, 4, 4, 5, 6, 7, 8],
            Stream::from([3, 2, 3, 4, 4, 3, 1, 5, 6, 5, 4, 7, 8])->increasingTrend()->toArray()
        );
    }
    
    public function test_filter_only_nonIncreasing_values(): void
    {
        self::assertSame(
            [8, 8, 6, 5, 5, 3, 1],
            Stream::from([8, 9, 8, 6, 5, 7, 5, 3, 4, 1, 3])->decreasingTrend()->toArray()
        );
    }
    
    public function test_dispatch_with_limit(): void
    {
        $threeStrings = Stream::empty()->limit(3)->collect();
        $twoInts = Stream::empty()->limit(2)->collect();
        
        $stream = Stream::from(['a', 1, 'b', 2, 'c', 3, 'd', 4])
            ->dispatch('is_string', [
                1 => $threeStrings,
                0 => $twoInts,
            ]);
        
        self::assertSame(8, $stream->count()->get());
        self::assertSame(['a', 'b', 'c'], $threeStrings->toArray());
        self::assertSame([1, 2], $twoInts->toArray());
    }
    
    public function test_dispatch_throws_excpetion_when_loop_is_detected(): void
    {
        //Assert
        $this->expectExceptionObject(StreamExceptionFactory::dispatchOperationCannotHandleLoops());
        
        //Arrange
        $stream = Stream::from([5, 'a', 2, 'b', 4,])->onlyIntegers();
        
        //Act
        $stream->dispatch('\is_string', [Reducers::sum(), $stream]);
    }
    
    public function test_classify(): void
    {
        $result = Stream::from(['a', 1, 'b', 2, 'c', 3, 'd'])
            ->classify(Discriminators::yesNo('is_string', 'strings', 'other'))
            ->flip()
            ->reduce(Reducers::countUnique());
        
        self::assertSame(['strings' => 4, 'other' => 3], $result->toArrayAssoc());
    }
    
    public function test_use_Discriminator_as_Mapper(): void
    {
        $result = Stream::from([6, 2, 4, 3, 1, 2, 5])
            ->map(Discriminators::evenOdd())
            ->reduce(Reducers::countUnique());
        
        self::assertSame(['even' => 4, 'odd' => 3], $result->toArrayAssoc());
    }
    
    public function test_putIn_allows_to_put_current_value_in_some_variable(): void
    {
        $var = null;
        $result = [];
        
        Stream::from(['a', 'b', 'c'])
            ->putIn($var)
            ->forEach(static function () use (&$result, &$var): void {
                $result[] = $var;
            });
        
        self::assertSame(['a', 'b', 'c'], $result);
    }
    
    public function test_putIn_allows_to_put_current_key_in_some_variable(): void
    {
        $var = null;
        $result = [];
        
        Stream::from(['a', 'b', 'c'])
            ->putIn($var, Check::KEY)
            ->forEach(static function () use (&$result, &$var): void {
                $result[] = $var;
            });
        
        self::assertSame([0, 1, 2], $result);
    }
    
    public function test_putIn_throws_exception_when_param_mode_is_invalid(): void
    {
        $this->expectExceptionObject(StreamExceptionFactory::invalidModeForPutInOperation());
        
        $var = null;
        Stream::empty()->putIn($var, Check::BOTH);
    }
    
    public function test_StoreIn_can_preserve_keys(): void
    {
        $letters = [];
        Stream::from(['a', 1, 'b', 2, 'c', 3])->onlyStrings()->storeIn($letters)->run();
        
        self::assertSame(['a', 2 => 'b', 4 => 'c'], $letters);
    }
    
    public function test_StoreIn_can_reindex_keys(): void
    {
        $letters = [];
        Stream::from(['a', 1, 'b', 2, 'c', 3])->onlyStrings()->storeIn($letters, true)->run();
        
        self::assertSame(['a', 'b', 'c'], $letters);
    }
    
    public function test_StoreIn_preserve_keys_with_onerror_handler(): void
    {
        $letters = [];
        Stream::from(['a', 1, 'b', 2, 'c', 3])
            ->onError(OnError::abort())
            ->onlyStrings()
            ->storeIn($letters)
            ->run();
        
        self::assertSame(['a', 2 => 'b', 4 => 'c'], $letters);
    }
    
    public function test_StoreIn_can_also_handle_all_ArrayAccess_instances(): void
    {
        $letters = new \ArrayObject();
        
        Stream::from(['a', 1, 'b', 2, 'c', 3])->onlyStrings()->storeIn($letters)->run();
        
        self::assertSame(['a', 2 => 'b', 4 => 'c'], $letters->getArrayCopy());
    }
    
    public function test_Segregate(): void
    {
        $result = Stream::from([1, 5, 2, 3, 2, 7, 4, 1, 6, 3, 5, 2, 4, 1, 7, 2, 3, 2, 6, 3, 2, 5, 8, 4])->segregate(3);
        
        self::assertSame([
            [0 => 1, 7 => 1, 13 => 1],
            [2 => 2, 4 => 2, 11 => 2, 15 => 2, 17 => 2, 20 => 2],
            [3 => 3, 9 => 3, 16 => 3, 19 => 3],
        ], $result->toArray());
    }
    
    public function test_Segregate_with_Limit(): void
    {
        $result = Stream::from([1, 5, 2, 3, 2, 7, 4, 1, 6, 3, 5, 2, 4, 1, 7, 2, 3, 2, 6, 3, 2, 5, 8, 4])
            ->segregate()
            ->limit(3)
            ->toArray();
        
        self::assertSame([
            [0 => 1, 7 => 1, 13 => 1],
            [2 => 2, 4 => 2, 11 => 2, 15 => 2, 17 => 2, 20 => 2],
            [3 => 3, 9 => 3, 16 => 3, 19 => 3],
        ], $result);
    }
    
    public function test_Segregate_with_Limit_zero(): void
    {
        $data = [1, 5, 2, 3, 2, 7, 4, 1, 6, 3, 5, 2, 4, 1, 7, 2, 3, 2, 6, 3, 2, 5, 8, 4];
        
        self::assertSame([], Stream::from($data)->segregate()->limit(0)->toArray());
    }
    
    public function test_Segregate_with_First(): void
    {
        $data = [5, 2, 3, 2, 7, 4, 1, 1, 6, 3, 5, 2, 4, 1, 7, 2, 3, 2, 6, 3, 2, 5, 8, 4];
        
        $result = Stream::from($data)->segregate(3)->first();
        
        self::assertSame(0, $result->key());
        self::assertSame([6 => 1, 7 => 1, 13 => 1], $result->get());
    }
    
    public function test_Segregate_with_Last(): void
    {
        $data = [1, 5, 2, 3, 2, 7, 4, 1, 6, 3, 5, 2, 4, 1, 7, 2, 3, 2, 6, 3, 2, 5, 8, 4];
        
        $result = Stream::from($data)->segregate(3)->last();
        
        self::assertSame(2, $result->key());
        self::assertSame([3 => 3, 9 => 3, 16 => 3, 19 => 3], $result->get());
    }
    
    public function test_Segregate_with_Count(): void
    {
        $data = [5, 2, 3, 2, 7, 4, 1, 6, 3, 1, 5, 2, 4, 1, 7, 2, 3, 2, 6, 3, 2, 5, 8, 4];
        
        self::assertSame(3, Stream::from($data)->segregate(3)->count()->get());
    }
    
    public function test_Segregate_throws_exception_when_number_of_buckets_is_invalid(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('buckets'));
        
        Stream::empty()->segregate(0);
    }
    
    public function test_Segregate_throws_exception_when_max_number_of_elements_in_buckets_is_invalid(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('limit'));
        
        Stream::empty()->segregate(1, false, null, 0);
    }
    
    public function test_Segregate_with_no_elements_on_input(): void
    {
        self::assertEmpty(Stream::empty()->segregate()->toArray());
    }
    
    public function test_Reverse_Reindex_Segregate(): void
    {
        $data = [5, 2, 3, 2, 7, 4, 1, 6, 3, 1, 5, 2, 4, 1, 7, 2, 3, 2, 6, 3, 2, 5, 8, 4];
        
        self::assertSame([
            [10 => 1, 14 => 1, 17 => 1],
            [3 => 2, 6 => 2, 8 => 2, 12 => 2, 20 => 2, 22 => 2],
            [4 => 3, 7 => 3, 15 => 3, 21 => 3],
        ], Stream::from($data)->reverse()->reindex()->segregate(3)->toArray());
    }
    
    public function test_Reverse_Segregate(): void
    {
        $data = [0 => 5, 1 => 2, 2 => 3, 3 => 2, 4 => 4, 5 => 1, 6 => 3];
        
        self::assertSame([
            [5 => 1],
            [3 => 2, 1 => 2],
            [6 => 3, 2 => 3],
        ], Stream::from($data)->reverse()->segregate(3)->toArray());
    }
    
    public function test_Categorize_with_Segregate(): void
    {
        $result = Stream::from([5, 2, 3, 2, 7, 4, 1, 6, 3, 1])
            ->categorize(Discriminators::evenOdd(), true)
            ->segregate()
            ->toArray();
        
        self::assertSame([
            ['even' => [2, 2, 4, 6]],
            ['odd' => [5, 3, 7, 1, 3, 1]],
        ], $result);
    }
    
    public function test_Sort_with_Segregate(): void
    {
        $result = Stream::from([5, 2, 3, 2, 7, 4, 1, 6, 3, 1, 5, 2, 4, 1, 7, 2, 3, 2, 6, 3, 2, 5, 8, 4])
            ->sort()
            ->segregate(3)
            ->toArray();
        
        //the exact order of elements sorted by native functions depends on PHP version
        if (\PHP_MAJOR_VERSION === 7) {
            $expected = [
                [6 => 1, 9 => 1, 13 => 1],
                [15 => 2, 3 => 2, 20 => 2, 17 => 2, 11 => 2, 1 => 2],
                [2 => 3, 19 => 3, 8 => 3, 16 => 3],
            ];
        } else {
            $expected = [
                [6 => 1, 9 => 1, 13 => 1],
                [1 => 2, 3 => 2, 11 => 2, 15 => 2, 17 => 2, 20 => 2],
                [2 => 3, 8 => 3, 16 => 3, 19 => 3],
            ];
        }
        
        self::assertSame($expected, $result);
    }
    
    public function test_Reverse_Reindex(): void
    {
        self::assertSame(
            ['d', 'c', 'b', 'a'],
            Stream::from(['a', 'b', 'c', 'd'])->reverse()->reindex()->toArrayAssoc()
        );
    }
    
    public function test_FilterBy_can_handle_ArrayAccess_implementations(): void
    {
        $rowset = [
            ['id' => 2, 'name' => 'Sue', 'age' => 22],
            ['id' => 9, 'name' => 'Chris', 'age' => 17],
            ['id' => 6, 'name' => 'Joanna', 'age' => 15],
            ['id' => 5, 'name' => 'Chris', 'age' => 24],
            ['id' => 7, 'name' => 'Sue', 'age' => 18],
        ];
        
        $result = Stream::from($rowset)
            ->map(static fn(array $row): \ArrayObject => new \ArrayObject($row))
            ->filterBy('name', 'Chris')
            ->map(static fn(\ArrayObject $row): array => $row->getArrayCopy())
            ->toArrayAssoc();
        
        self::assertSame([
            1 => ['id' => 9, 'name' => 'Chris', 'age' => 17],
            3 => ['id' => 5, 'name' => 'Chris', 'age' => 24],
        ], $result);
    }
    
    public function test_Reducer_can_be_used_as_Consumer(): void
    {
        $reducer = Reducers::sum();
        
        Stream::from([1, 'a', 2, 'b', 3, 'd', 4])->callWhen('is_int', $reducer)->run();
        
        self::assertSame(10, $reducer->result());
    }
    
    public function test_gatherWhile_with_stacked_producers(): void
    {
        $result = Stream::from(['a b', 'c d'])
            ->tokenize()
            ->call($counter = Consumers::counter())
            ->gatherWhile(fn(): bool => $counter->get() <= 3, true)
            ->toArray();
        
        self::assertSame([['a', 'b', 'c']], $result);
        self::assertSame(4, $counter->count());
    }
    
    public function test_gatherUntil_with_stacked_producers(): void
    {
        $result = Stream::from(['a b', 'c d'])
            ->tokenize()
            ->call($counter = Consumers::counter())
            ->gatherUntil(fn(): bool => $counter->count() > 3, true)
            ->toArray();
        
        self::assertSame([['a', 'b', 'c']], $result);
        self::assertSame(4, $counter->count());
    }
    
    public function test_gather_with_stacked_producers_and_limit(): void
    {
        $result = Stream::from(['a b', 'c d'])
            ->tokenize()
            ->call($counter = Consumers::counter())
            ->limit(3)
            ->gather(true)
            ->toArray();
        
        self::assertSame([['a', 'b', 'c']], $result);
        self::assertSame(3, $counter->count());
    }
    
    public function test_UnpackTuple(): void
    {
        $result = Stream::from([3 => 'a', 2 => 'b', 1 => 'c'])
            ->makeTuple()
            ->map(static fn(array $t): array => [$t[0] + 1, \strtoupper($t[1])])
            ->unpackTuple()
            ->toArrayAssoc();
        
        self::assertSame([4 => 'A', 3 => 'B', 2 => 'C'], $result);
    }
    
    public function test_Zip_without_sources(): void
    {
        $result = Stream::from([3 => 'a', 2 => 'b', 1 => 'c'])->zip()->toArrayAssoc();
        
        self::assertSame([
            3 => ['a'],
            2 => ['b'],
            1 => ['c'],
        ], $result);
    }
    
    /**
     * @dataProvider getDataForTestZipWithOneSource
     */
    #[DataProvider('getDataForTestZipWithOneSource')]
    public function test_Zip_with_one_source($source): void
    {
        $result = Stream::from([3 => 'a', 2 => 'b', 1 => 'c'])
            ->zip($source)
            ->toArrayAssoc();
        
        self::assertSame([
            3 => ['a', 2],
            2 => ['b', 4],
            1 => ['c', 6],
        ], $result);
    }
    
    public static function getDataForTestZipWithOneSource(): array
    {
        return [
            'array' => [[2, 4, 6]],
            'producer' => [Producers::sequentialInt(2, 2)],
            'callable' => [static fn(): array => ['z' => 2, 4, 3 => 6]],
            'stream' => [Stream::from(['2', '4', '6'])->castToInt()],
        ];
    }
    
    public function test_CountIn(): void
    {
        $result = Stream::from(['a', 'b', 1, 'c', 2, 4])
            ->onlyStrings()
            ->countIn($count)
            ->toString('');
        
        self::assertSame('abc', $result);
        self::assertSame(3, $count);
    }
    
    public function test_Unzip(): void
    {
        $rowset = [
            ['id' => 2, 'name' => 'Sue', 'age' => 22],
            ['id' => 9, 'name' => 'Chris', 'age' => 17],
            ['id' => 6, 'name' => 'Joanna', 'age' => 15],
            ['id' => 5, 'name' => 'Chris', 'age' => 24],
            ['id' => 7, 'name' => 'Sue', 'age' => 18],
        ];
        
        $ids = [];
        $uniqueNames = Stream::empty()->unique()->collect(true);
        $avgAge = Reducers::average(2);
        
        Stream::from($rowset)
            ->unzip(
                Collectors::array($ids, false),
                $uniqueNames,
                $avgAge
            )->run();
        
        self::assertSame([2, 9, 6, 5, 7], $ids);
        self::assertSame(['Sue', 'Chris', 'Joanna'], $uniqueNames->get());
        self::assertSame((22 + 17 + 15 + 24 + 18) / 5, $avgAge->result());
    }
    
    public function test_ArrayCollector_with_keys(): void
    {
        $strings = [];
        $collector = Collectors::array($strings);
        
        Stream::from(['a', 1, 'b', 2])
            ->onlyStrings()
            ->collectIn($collector)
            ->run();
        
        self::assertSame(['a', 2 => 'b'], $strings);
        
        self::assertSame(['a', 2 => 'b'], $collector->toArray());
        self::assertSame(2, $collector->count());
    }
    
    public function test_ArrayCollector_without_keys(): void
    {
        $strings = [];
        $collector = Collectors::array($strings, false);
        
        Stream::from(['a', 1, 'b', 2])
            ->onlyStrings()
            ->collectIn($collector)
            ->run();
        
        self::assertSame(['a', 'b'], $strings);
        
        self::assertSame(['a', 'b'], $collector->toArray());
        self::assertSame(2, $collector->count());
    }
    
    public function test_ArrayCollector_reindex(): void
    {
        $strings = [];
        $collector = Collectors::array($strings);
        
        Stream::from(['a', 1, 'b', 2])
            ->onlyStrings()
            ->collectIn($collector, true)
            ->run();
        
        self::assertSame(['a', 'b'], $strings);
        
        self::assertSame(['a', 'b'], $collector->toArray());
        self::assertSame(2, $collector->count());
    }
    
    public function test_Sort_by_value_asc(): void
    {
        $data = [5, 2, 4, 1];
        $expected = [1, 2, 4, 5];
        
        self::assertSame($expected, Stream::from($data)->sort()->toArray());
        self::assertSame($expected, Stream::from($data)->sort(By::value())->toArray());
        self::assertSame($expected, Stream::from($data)->sort(By::valueAsc())->toArray());
        
        self::assertSame($expected, Stream::from($data)->rsort(By::value(null, true))->toArray());
        self::assertSame($expected, Stream::from($data)->rsort(By::valueDesc())->toArray());
    }
    
    public function test_Sort_by_value_desc(): void
    {
        $data = [5, 2, 4, 1];
        $expected = [5, 4, 2, 1];
        
        self::assertSame($expected, Stream::from($data)->rsort()->toArray());
        self::assertSame($expected, Stream::from($data)->rsort(By::value())->toArray());
        
        self::assertSame($expected, Stream::from($data)->sort(By::value(null, true))->toArray());
        self::assertSame($expected, Stream::from($data)->sort(By::valueDesc())->toArray());
    }
    
    public function test_Sort_by_key_asc(): void
    {
        $data = [5 => 'a', 2 => 'c', 4 => 'b', 1 => 'd'];
        $expected = [1 => 'd', 2 => 'c', 4 => 'b', 5 => 'a'];
        
        self::assertSame($expected, Stream::from($data)->sort(By::key())->toArrayAssoc());
        self::assertSame($expected, Stream::from($data)->sort(By::keyAsc())->toArrayAssoc());
        
        self::assertSame($expected, Stream::from($data)->rsort(By::key(null, true))->toArrayAssoc());
        self::assertSame($expected, Stream::from($data)->rsort(By::keyDesc())->toArrayAssoc());
    }
    
    public function test_Sort_by_key_desc(): void
    {
        $data = [5 => 'a', 2 => 'c', 4 => 'b', 1 => 'd'];
        $expected = [5 => 'a', 4 => 'b', 2 => 'c', 1 => 'd'];
        
        self::assertSame($expected, Stream::from($data)->rsort(By::key())->toArrayAssoc());
        
        self::assertSame($expected, Stream::from($data)->sort(By::key(null, true))->toArrayAssoc());
        self::assertSame($expected, Stream::from($data)->sort(By::keyDesc())->toArrayAssoc());
    }
    
    /**
     * @dataProvider getDataForTestSortByValueAndKey
     */
    #[DataProvider('getDataForTestSortByValueAndKey')]
    public function test_Sort_by_value_and_key(Sorting $normal, Sorting $reversed, array $expected): void
    {
        $data = [3 => 'b', 5 => 'a', 2 => 'c', 4 => 'b', 1 => 'd', 6 => 'a'];
        
        self::assertSame($expected, Stream::from($data)->sort($normal)->toArrayAssoc());
        self::assertSame($expected, Stream::from($data)->rsort($reversed)->toArrayAssoc());
    }
    
    public static function getDataForTestSortByValueAndKey(): array
    {
        return [
            //normal, reversed, expected
            'value_asc_key_asc' => [
                By::both(Value::asc(), Key::asc()),
                By::both(Value::desc(), Key::desc()),
                [5 => 'a', 6 => 'a', 3 => 'b', 4 => 'b', 2 => 'c', 1 => 'd'],
            ],
            'value_asc_key_desc' => [
                By::both(Value::asc(), Key::desc()),
                By::both(Value::desc(), Key::asc()),
                [6 => 'a', 5 => 'a', 4 => 'b', 3 => 'b', 2 => 'c', 1 => 'd']
            ],
            'value_desc_key_desc' => [
                By::both(Value::desc(), Key::desc()),
                By::both(Value::asc(), Key::asc()),
                [1 => 'd', 2 => 'c', 4 => 'b', 3 => 'b', 6 => 'a', 5 => 'a']
            ],
            'value_desc_key_asc' => [
                By::both(Value::desc(), Key::asc()),
                By::both(Value::asc(), Key::desc()),
                [1 => 'd', 2 => 'c', 3 => 'b', 4 => 'b', 5 => 'a', 6 => 'a']
            ],
        ];
    }
    
    /**
     * @dataProvider getDataForTestSortByKeyAndValue
     */
    #[DataProvider('getDataForTestSortByKeyAndValue')]
    public function test_Sort_by_key_and_value(Sorting $normal, Sorting $reversed, array $expected): void
    {
        $keys = [1, 3, 1, 2];
        $values = ['a', 'a', 'b', 'b'];
        
        $result1 = Stream::from($keys)
            ->zip($values)
            ->unpackTuple()
            ->sort($normal)
            ->makeTuple()
            ->toArray();
        
        $result2 = Stream::from($keys)
            ->zip($values)
            ->unpackTuple()
            ->rsort($reversed)
            ->makeTuple()
            ->toArray();
        
        self::assertSame($expected, $result1);
        self::assertSame($expected, $result2);
    }
    
    public static function getDataForTestSortByKeyAndValue(): array
    {
        return [
            //normal, reversed, expected
            'key_asc_value_asc' => [
                By::both(Key::asc(), Value::asc()),
                By::both(Key::desc(), Value::desc()),
                [
                    [1, 'a'],
                    [1, 'b'],
                    [2, 'b'],
                    [3, 'a'],
                ]
            ],
            'key_asc_value_desc' => [
                By::both(Key::asc(), Value::desc()),
                By::both(Key::desc(), Value::asc()),
                [
                    [1, 'b'],
                    [1, 'a'],
                    [2, 'b'],
                    [3, 'a'],
                ]
            ],
            'key_desc_value_desc' => [
                By::both(Key::desc(), Value::desc()),
                By::both(Key::asc(), Value::asc()),
                [
                    [3, 'a'],
                    [2, 'b'],
                    [1, 'b'],
                    [1, 'a'],
                ]
            ],
            'key_desc_value_asc' => [
                By::both(Key::desc(), Value::asc()),
                By::both(Key::asc(), Value::desc()),
                [
                    [3, 'a'],
                    [2, 'b'],
                    [1, 'a'],
                    [1, 'b'],
                ]
            ],
        ];
    }
    
    /**
     * @dataProvider getDataForTestSortByCustomComparator
     */
    #[DataProvider('getDataForTestSortByCustomComparator')]
    public function test_Sort_by_custom_comparator($sorting, array $expected): void
    {
        self::assertSame(
            $expected,
            Stream::from([5 => 'a', 2 => 'c', 3 => 'a', 1 => 'b', 4 => 'c'])->sort($sorting)->toArrayAssoc()
        );
    }
    
    public static function getDataForTestSortByCustomComparator(): array
    {
        $valueAscKeyDesc = [
            5 => 'a',
            3 => 'a',
            1 => 'b',
            4 => 'c',
            2 => 'c',
        ];
        
        $valueAsc = [
            5 => 'a',
            3 => 'a',
            1 => 'b',
            2 => 'c',
            4 => 'c',
        ];
        
        $valueComparator = static fn(string $a, string $b): int => $a <=> $b;
        $valueAndKeyComparator = static fn(string $v1, string $v2, int $k1, int $k2): int => $v1 <=> $v2 ?: $k2 <=> $k1;
        
        return [
            //sorting, expected
            0 => [null, $valueAsc],
            [$valueComparator, $valueAsc],
            
            2 => [Comparators::default(), $valueAsc],
            [Comparators::getAdapter($valueComparator), $valueAsc],
            [Comparators::getAdapter($valueComparator), $valueAsc],
            
            5 => [By::value(), $valueAsc],
            [By::valueAsc(), $valueAsc],
            [By::value($valueComparator), $valueAsc],
            [By::valueAsc($valueComparator), $valueAsc],
            [By::value(Comparators::default()), $valueAsc],
            [By::valueAsc(Comparators::default()), $valueAsc],
            
            11 => [$valueAndKeyComparator, $valueAscKeyDesc],
            [Comparators::getAdapter($valueAndKeyComparator), $valueAscKeyDesc],
            [Comparators::getAdapter($valueAndKeyComparator), $valueAscKeyDesc],
            
            15 => [By::assoc($valueAndKeyComparator), $valueAscKeyDesc],
            [By::assocAsc($valueAndKeyComparator), $valueAscKeyDesc],
            [By::assoc(Comparators::getAdapter($valueAndKeyComparator)), $valueAscKeyDesc],
            [By::assocAsc(Comparators::getAdapter($valueAndKeyComparator)), $valueAscKeyDesc],
        ];
    }
    
    public function test_filter_OR_value(): void
    {
        self::assertSame(
            [4 => 6, 3 => 5, 6 => 1, 1 => 2],
            Stream::from([5 => 4, 4 => 6, 3 => 5, 2 => 3, 6 => 1, 1 => 2])
                ->filter(Filters::OR(Filters::lessThan(3), Filters::greaterThan(4)))
                ->toArrayAssoc()
        );
    }
    
    public function test_filter_OR_key(): void
    {
        self::assertSame(
            [5 => 4, 2 => 3, 6 => 1, 1 => 2],
            Stream::from([5 => 4, 4 => 6, 3 => 5, 2 => 3, 6 => 1, 1 => 2])
                ->filter(Filters::OR(Filters::lessThan(3), Filters::greaterThan(4)), Check::KEY)
                ->toArrayAssoc()
        );
    }
    
    public function test_filter_OR_both(): void
    {
        self::assertSame(
            [6 => 1, 1 => 2],
            Stream::from([5 => 4, 4 => 6, 3 => 5, 2 => 3, 6 => 1, 1 => 2])
                ->filter(Filters::OR(Filters::lessThan(3), Filters::greaterThan(4)), Check::BOTH)
                ->toArrayAssoc()
        );
    }
    
    public function test_filter_OR_any(): void
    {
        self::assertSame(
            [5 => 4, 4 => 6, 3 => 5, 2 => 3, 6 => 1, 1 => 2],
            Stream::from([5 => 4, 4 => 6, 3 => 5, 2 => 3, 6 => 1, 1 => 2])
                ->filter(Filters::OR(Filters::lessThan(3), Filters::greaterThan(4)), Check::ANY)
                ->toArrayAssoc()
        );
    }
    
    public function test_omit_OR_value(): void
    {
        self::assertSame(
            [5 => 4, 2 => 3],
            Stream::from([5 => 4, 4 => 6, 3 => 5, 2 => 3, 6 => 1, 1 => 2])
                ->omit(Filters::OR(Filters::lessThan(3), Filters::greaterThan(4)))
                ->toArrayAssoc()
        );
    }
    
    public function test_omit_OR_key(): void
    {
        self::assertSame(
            [4 => 6, 3 => 5],
            Stream::from([5 => 4, 4 => 6, 3 => 5, 2 => 3, 6 => 1, 1 => 2])
                ->omit(Filters::OR(Filters::lessThan(3), Filters::greaterThan(4)), Check::KEY)
                ->toArrayAssoc()
        );
    }
    
    public function test_omit_OR_both(): void
    {
        self::assertSame(
            [5 => 4, 4 => 6, 3 => 5, 2 => 3],
            Stream::from([5 => 4, 4 => 6, 3 => 5, 2 => 3, 6 => 1, 1 => 2])
                ->omit(Filters::OR(Filters::lessThan(3), Filters::greaterThan(4)), Check::BOTH)
                ->toArrayAssoc()
        );
    }
    
    public function test_omit_OR_any(): void
    {
        self::assertSame(
            [],
            Stream::from([5 => 4, 4 => 6, 3 => 5, 2 => 3, 6 => 1, 1 => 2])
                ->omit(Filters::OR(Filters::lessThan(3), Filters::greaterThan(4)), Check::ANY)
                ->toArrayAssoc()
        );
    }
    
    public function test_filter_stream_by_onlyWith_allow_nulls(): void
    {
        $this->examineOnlyWithFilter(
            Filters::onlyWith(['a', 'b'], true),
            new \ArrayObject(['a' => null, 'b' => 1]),
            new \ArrayObject(['a' => 1, 'foo' => 1])
        );
    }
    
    public function test_filter_stream_by_onlyWith_disallow_nulls(): void
    {
        $this->examineOnlyWithFilter(
            Filters::onlyWith(['a', 'b']), ['a' => 1, 'b' => 2], new \ArrayObject(['a' => 3, 'b' => null])
        );
    }
    
    private function examineOnlyWithFilter(Filter $filter, $good, $wrong): void
    {
        $mode = 'value';
        $value = $filter->checkValue();
        $valueNot = $value->negate();
        
        $filters = ['positive' => ['value' => $value], 'negative' => ['value' => $valueNot]];
        
        $data = $this->prepareProducerForTest($good, $wrong);
        $expected = $this->prepareExpectedResults($good, $wrong);
        
        foreach (['positive', 'negative'] as $posneg) {
            /* @var $filter Filter */
            $filter = $filters[$posneg][$mode];
            
            $expectedFilter = $expected[$posneg]['filter'][$mode];
            $expectedOmit = $expected[$posneg]['omit'][$mode];
            
            $case = $posneg.'_'.$mode;
            
            self::assertEquals($expectedFilter, $this->filterData($data, $filter), $case.'_0');
            self::assertEquals($expectedOmit, $this->omitData($data, $filter), $case.'_1');
            self::assertEquals($expectedFilter, $this->filterDataOldWay($data, $filter), $case.'_2');
            self::assertEquals($expectedOmit, $this->omitDataOldWay($data, $filter), $case.'_3');
        }
    }
    
    public function test_filter_stream_by_isBool(): void
    {
        $this->examineFilter(Filters::isBool(), true, 1);
    }
    
    public function test_filter_stream_by_isInt(): void
    {
        $this->examineFilter(Filters::isInt(), 1, 'foo');
    }
    
    public function test_filter_stream_by_isFloat(): void
    {
        $this->examineFilter(Filters::isFloat(), 1.0, 5);
    }
    
    public function test_filter_stream_by_isString(): void
    {
        $this->examineFilter(Filters::isString(), 'foo', 1);
    }
    
    public function test_filter_stream_by_isDateTime(): void
    {
        $this->examineFilter(Filters::isDateTime(), 'now', 'foo');
    }
    
    public function test_filter_stream_by_isCountable(): void
    {
        $this->examineFilter(Filters::isCountable(), ['a'], 5);
    }
    
    public function test_filter_stream_by_isEmpty(): void
    {
        $this->examineFilter(Filters::isEmpty(), '', 'foo');
    }
    
    public function test_filter_stream_by_isNull(): void
    {
        $this->examineFilter(Filters::isNull(), null, 'foo');
    }
    
    public function test_filter_stream_by_isNumeric(): void
    {
        $this->examineFilter(Filters::isNumeric(), '25.0', 'foo');
    }
    
    public function test_filter_stream_by_notEmpty(): void
    {
        $this->examineFilter(Filters::notEmpty(), '25.0', '');
    }
    
    public function test_filter_stream_by_notNull(): void
    {
        $this->examineFilter(Filters::notNull(), '', null);
    }
    
    public function test_filter_stream_by_isArray(): void
    {
        $this->examineFilter(Filters::isArray(), [], '');
    }
    
    public function test_filter_stream_by_time_is(): void
    {
        $time = '2015-05-05 12:12:12';
        $wrong = '2020-02-02 12:00:00';
        
        $this->examineFilter(Filters::time()->is($time), $time, $wrong);
    }
    
    public function test_filter_stream_by_time_isNot(): void
    {
        $time = '2015-05-05 12:12:12';
        $wrong = '2020-02-02 12:00:00';
        
        $this->examineFilter(Filters::time()->isNot($time), $wrong, $time);
    }
    
    public function test_filter_stream_by_time_before(): void
    {
        [$d1, $d2] = $this->dates();
        
        $this->examineFilter(Filters::time()->before($d2), $d1, $d2);
    }
    
    public function test_filter_stream_by_time_from(): void
    {
        [$d1, $d2] = $this->dates();
        
        $this->examineFilter(Filters::time()->from($d2), $d2, $d1);
    }
    
    public function test_filter_stream_by_time_after(): void
    {
        [, $d2, $d3] = $this->dates();
        
        $this->examineFilter(Filters::time()->after($d2), $d3, $d2);
    }
    
    public function test_filter_stream_by_time_until(): void
    {
        [$d1, $d2, $d3] = $this->dates();
        
        $this->examineFilter(Filters::time()->until($d2), $d1, $d3);
    }
    
    public function test_filter_stream_by_time_between(): void
    {
        [, $d2, $d3, $d4] = $this->dates();
        
        $this->examineFilter(Filters::time()->between($d2, $d3), $d2, $d4);
    }
    
    public function test_filter_stream_by_time_outside(): void
    {
        [$d1, $d2, $d3] = $this->dates();
        
        $this->examineFilter(Filters::time()->outside($d2, $d3), $d1, $d3);
    }
    
    public function test_filter_stream_by_time_inside(): void
    {
        [$d1, $d2, $d3] = $this->dates();
        
        $this->examineFilter(Filters::time()->inside($d1, $d3), $d2, $d1);
    }
    
    public function test_filter_stream_by_time_notInside(): void
    {
        [$d1, $d2, $d3, $d4] = $this->dates();
        
        $this->examineFilter(Filters::time()->notInside($d2, $d4), $d1, $d3);
    }
    
    public function test_filter_stream_by_time_inSet(): void
    {
        [$d1, $d2, $d3, $d4] = $this->dates();
        
        $this->examineFilter(Filters::time()->inSet([$d1, $d3, $d4]), $d4, $d2);
    }
    
    public function test_filter_stream_by_time_notInSet(): void
    {
        [$d1, $d2, $d3, $d4] = $this->dates();
        
        $this->examineFilter(Filters::time()->notInSet([$d1, $d3, $d4]), $d2, $d3);
    }
    
    private function dates(): array
    {
        $d1 = new \DateTime('2024-01-31 15:00:00');
        $d2 = new \DateTime('2024-01-31 15:30:00');
        $d3 = '2024-01-31 16:00:00';
        $d4 = new \DateTimeImmutable('2024-01-31 17:30:00');
        $d5 = '2024-01-31 18:00:00';
        
        return [$d1, $d2, $d3, $d4, $d5];
    }
    
    public function test_filter_stream_by_string_contains_case_sensitive(): void
    {
        $this->examineFilter(Filters::string()->contains('foo'), 'aaafooaaa', 'aaaFOOaaa');
    }
    
    public function test_filter_stream_by_string_contains_case_insensitive(): void
    {
        $this->examineFilter(Filters::string()->contains('foo', true), 'aaaFOOaaa', 'aaabaraaa');
    }
    
    public function test_filter_string_notContains_case_sensitive(): void
    {
        $this->examineFilter(Filters::string()->notContains('foo'), 'aaaFOOaaa', 'aaafooaaa');
    }
    
    public function test_filter_string_notContains_case_insensitive(): void
    {
        $this->examineFilter(Filters::string()->notContains('foo', true), 'aaabaraaa', 'aaaFOOaaa');
    }
    
    public function test_filter_stream_by_string_endsWith_case_sensitive(): void
    {
        $this->examineFilter(Filters::string()->endsWith('foo'), 'aaafoo', 'aaaFOO');
    }
    
    public function test_filter_stream_by_string_endsWith_case_insensitive(): void
    {
        $this->examineFilter(Filters::string()->endsWith('foo', true), 'aaaFOO', 'aaabar');
    }
    
    public function test_filter_stream_by_string_notEndsWith_case_sensitive(): void
    {
        $this->examineFilter(Filters::string()->notEndsWith('foo'), 'aaaFOO', 'aaafoo');
    }
    
    public function test_filter_stream_by_string_notEndsWith_case_insensitive(): void
    {
        $this->examineFilter(Filters::string()->notEndsWith('foo', true), 'aaabar', 'aaaFOO');
    }
    
    public function test_filter_stream_by_string_inSet_case_sensitive(): void
    {
        $this->examineFilter(Filters::string()->inSet(['aaa', 'foo', 'bbb'], true)->caseSensitive(), 'foo', 'FOO');
    }
    
    public function test_filter_stream_by_string_inSet_case_insensitive(): void
    {
        $this->examineFilter(Filters::string()->inSet(['aaa', 'foo', 'bbb'])->ignoreCase(), 'Foo', 'zoo');
    }
    
    public function test_filter_stream_by_string_notInSet_case_sensitive(): void
    {
        $this->examineFilter(Filters::string()->notInSet(['aaa', 'foo', 'bbb']), 'FOO', 'foo');
    }
    
    public function test_filter_stream_by_string_notInSet_case_insensitive(): void
    {
        $this->examineFilter(Filters::string()->notInSet(['aaa', 'foo', 'bbb'], true), 'zoo', 'Foo');
    }
    
    public function test_filter_stream_by_string_is_case_sensitive(): void
    {
        $this->examineFilter(Filters::string()->is('foo'), 'foo', 'FOO');
    }
    
    public function test_filter_stream_by_string_is_case_insensitive(): void
    {
        $this->examineFilter(Filters::string()->is('foo', true), 'Foo', 'zoo');
    }
    
    public function test_filter_stream_by_string_isNot_case_sensitive(): void
    {
        $this->examineFilter(Filters::string()->isNot('foo'), 'FOO', 'foo');
    }
    
    public function test_filter_stream_by_string_isNot_case_insensitive(): void
    {
        $this->examineFilter(Filters::string()->isNot('foo', true), 'zoo', 'Foo');
    }
    
    public function test_filter_stream_by_string_startsWith_case_sensitive(): void
    {
        $this->examineFilter(Filters::string()->startsWith('foo'), 'foo', 'FOO');
    }
    
    public function test_filter_stream_by_string_startsWith_case_insensitive(): void
    {
        $this->examineFilter(Filters::string()->startsWith('foo', true), 'Foo', 'zoo');
    }
    
    public function test_filter_stream_by_string_notStartsWith_case_sensitive(): void
    {
        $this->examineFilter(Filters::string()->notStartsWith('foo'), 'FOO', 'foo');
    }
    
    public function test_filter_stream_by_string_notStartsWith_case_insensitive(): void
    {
        $this->examineFilter(Filters::string()->notStartsWith('foo', true), 'zoo', 'Foo');
    }
    
    public function test_filter_stream_by_size_count_equal(): void
    {
        $this->examineFilter(Filters::size()->eq(1), ['a'], []);
    }
    
    public function test_filter_stream_by_size_count_notEqual(): void
    {
        $this->examineFilter(Filters::size()->ne(1), [], ['a']);
    }
    
    public function test_filter_stream_by_size_count_lessThan(): void
    {
        $this->examineFilter(Filters::size()->lt(1), [], ['a']);
    }
    
    public function test_filter_stream_by_size_count_lessOrEqual(): void
    {
        $this->examineFilter(Filters::size()->le(1), ['a'], [1, 2]);
    }
    
    public function test_filter_stream_by_size_count_greaterThan(): void
    {
        $this->examineFilter(Filters::size()->gt(0), ['a'], []);
    }
    
    public function test_filter_stream_by_size_count_greaterOrEqual(): void
    {
        $this->examineFilter(Filters::size()->ge(1), ['a'], []);
    }
    
    public function test_filter_stream_by_size_count_between(): void
    {
        $this->examineFilter(Filters::size()->between(1, 2), [1], [1,2,3]);
    }
    
    public function test_filter_stream_by_size_count_outside(): void
    {
        $this->examineFilter(Filters::size()->outside(1, 2), [1,2,3], [1,2]);
    }
    
    public function test_filter_stream_by_size_count_inside(): void
    {
        $this->examineFilter(Filters::size()->inside(1, 3), [1,2], [1,2,3]);
    }
    
    public function test_filter_stream_by_size_count_notInside(): void
    {
        $this->examineFilter(Filters::size()->notInside(1, 3), [1,2,3], [1,2]);
    }
    
    public function test_filter_stream_by_size_length_equal(): void
    {
        $this->examineFilter(Filters::length()->eq(1), 'a', 'bb');
    }
    
    public function test_filter_stream_by_size_length_notEqual(): void
    {
        $this->examineFilter(Filters::length()->ne(1), 'bb', 'a');
    }
    
    public function test_filter_stream_by_size_length_lessThan(): void
    {
        $this->examineFilter(Filters::length()->lt(2), 'a', 'bb');
    }
    
    public function test_filter_stream_by_size_length_lessOrEqual(): void
    {
        $this->examineFilter(Filters::length()->le(1), 'a', 'ab');
    }
    
    public function test_filter_stream_by_size_length_greaterThan(): void
    {
        $this->examineFilter(Filters::length()->gt(1), 'bb', 'a');
    }
    
    public function test_filter_stream_by_size_length_greaterOrEqual(): void
    {
        $this->examineFilter(Filters::length()->ge(2), 'aa', 'b');
    }
    
    public function test_filter_stream_by_size_length_between(): void
    {
        $this->examineFilter(Filters::length()->between(1, 2), 'a', 'abc');
    }
    
    public function test_filter_stream_by_size_length_outside(): void
    {
        $this->examineFilter(Filters::length()->outside(1, 2), 'abc', 'ab');
    }
    
    public function test_filter_stream_by_size_length_inside(): void
    {
        $this->examineFilter(Filters::length()->inside(1, 3), 'ab', 'abc');
    }
    
    public function test_filter_stream_by_size_length_notInside(): void
    {
        $this->examineFilter(Filters::length()->notInside(1, 3), 'abc', 'ab');
    }
    
    public function test_filter_stream_by_equal(): void
    {
        $this->examineFilter(Filters::equal(3), '3', 5);
    }
    
    public function test_filter_stream_by_notEqual(): void
    {
        $this->examineFilter(Filters::notEqual(3), 5, '3');
    }
    
    public function test_filter_stream_by_same(): void
    {
        $this->examineFilter(Filters::same(3), 3, '3');
    }
    
    public function test_filter_stream_by_notSame(): void
    {
        $this->examineFilter(Filters::notSame(3), '3', 3);
    }
    
    public function test_filter_stream_by_number_between(): void
    {
        $this->examineFilter(Filters::number()->between(2, 3), 2, 1);
    }
    
    public function test_filter_stream_by_number_outside(): void
    {
        $this->examineFilter(Filters::number()->outside(2, 3), 1, 3);
    }
    
    public function test_filter_stream_by_number_equal(): void
    {
        $this->examineFilter(Filters::number()->eq(1), 1, 2);
    }
    
    public function test_filter_stream_by_number_notEqual(): void
    {
        $this->examineFilter(Filters::number()->ne(1), 2, 1);
    }
    
    public function test_filter_stream_by_number_greaterOrEqual(): void
    {
        $this->examineFilter(Filters::number()->ge(2), 2, 1);
    }
    
    public function test_filter_stream_by_number_lessThan(): void
    {
        $this->examineFilter(Filters::number()->lt(2), 1, 2);
    }
    
    public function test_filter_stream_by_number_greaterThan(): void
    {
        $this->examineFilter(Filters::number()->gt(1), 2, 1);
    }
    
    public function test_filter_stream_by_number_lessOrEqual(): void
    {
        $this->examineFilter(Filters::number()->le(1), 1, 2);
    }
    
    public function test_filter_stream_by_number_inside(): void
    {
        $this->examineFilter(Filters::number()->inside(1, 2), 1.5, 1);
    }
    
    public function test_filter_stream_by_number_notInside(): void
    {
        $this->examineFilter(Filters::number()->notInside(1, 2), 1, 1.5);
    }
    
    public function test_filter_stream_by_number_isEven(): void
    {
        $this->examineFilter(Filters::number()->isEven(), 4, 3);
    }
    
    public function test_filter_stream_by_number_isOdd(): void
    {
        $this->examineFilter(Filters::number()->isOdd(), 3, 4);
    }
    
    public function test_filter_stream_by_onlyIn_ints(): void
    {
        $this->examineFilter(Filters::onlyIn([1, 3]), 3, 2);
    }
    
    public function test_filter_stream_by_onlyIn_strings(): void
    {
        $this->examineFilter(Filters::onlyIn(['a', 'b']), 'a', 'c');
    }
    
    public function test_filter_stream_by_onlyIn_others(): void
    {
        $this->examineFilter(Filters::onlyIn([1.0, 2.0]), 1.0, 1.5);
    }
    
    public function test_filter_stream_by_onlyIn_mixed(): void
    {
        $this->examineFilter(Filters::onlyIn([1, '1', 1.0, '2']), 1, 2);
        $this->examineFilter(Filters::onlyIn([1, '1', 1.0, '2']), '1', 2);
        $this->examineFilter(Filters::onlyIn([1, '1', 1.0, '2']), 1.0, 2);
    }
    
    public function test_filter_stream_by_one_arg_callable(): void
    {
        $this->examineFilter(Filters::getAdapter(static fn($val): bool => $val === 1), 1, 2);
    }
    
    private function examineFilter(Filter $filter, $good, $wrong): void
    {
        foreach ($this->testParams($filter, $good, $wrong) as $case => $testParam) {
            [$data, $filter, $expectedFilter, $expectedOmit] = $testParam;
            
            self::assertEquals($expectedFilter, $this->filterData($data, $filter), $case.'_0');
            self::assertEquals($expectedOmit, $this->omitData($data, $filter), $case.'_1');
            self::assertEquals($expectedFilter, $this->filterDataOldWay($data, $filter), $case.'_2');
            self::assertEquals($expectedOmit, $this->omitDataOldWay($data, $filter), $case.'_3');
        }
    }
    
    private function filterData(Producer $producer, Filter $filter): array
    {
        return $producer->stream()->filter($filter)->makeTuple()->toArray();
    }
    
    private function omitData(Producer $producer, Filter $filter): array
    {
        return $producer->stream()->omit($filter)->makeTuple()->toArray();
    }
    
    private function filterDataOldWay(Producer $producer, Filter $filter): array
    {
        return $producer->stream()->onError(OnError::abort())->filter($filter)->makeTuple()->toArray();
    }
    
    private function omitDataOldWay(Producer $producer, Filter $filter): array
    {
        return $producer->stream()->onError(OnError::abort())->omit($filter)->makeTuple()->toArray();
    }
    
    /**
     * @param mixed $good
     * @param mixed $wrong
     * @return iterable<string, array{Producer, Filter, array, array}>
     */
    private function testParams(Filter $filter, $good, $wrong): iterable
    {
        $filters = $this->prepareFiltersForTest($filter);
        $producer = $this->prepareProducerForTest($good, $wrong);
        $expected = $this->prepareExpectedResults($good, $wrong);
        
        foreach (['positive', 'negative'] as $posneg) {
            foreach (['value', 'key', 'both', 'any'] as $mode) {
                yield $posneg.'_'.$mode => [
                    $producer,
                    $filters[$posneg][$mode],
                    $expected[$posneg]['filter'][$mode],
                    $expected[$posneg]['omit'][$mode]
                ];
            }
        }
    }
    
    private function prepareExpectedResults($good, $wrong): array
    {
        return [
            'positive' => [
                'filter' => [
                    'value' => [[$wrong, $good], [$good, $good]],
                    'key' => [[$good, $wrong], [$good, $good]],
                    'both' => [[$good, $good]],
                    'any' => [[$good, $wrong], [$wrong, $good], [$good, $good]],
                ],
                'omit' => [
                    'value' => [[$good, $wrong], [$wrong, $wrong]],
                    'key' => [[$wrong, $good], [$wrong, $wrong]],
                    'both' => [[$good, $wrong], [$wrong, $good], [$wrong, $wrong]],
                    'any' => [[$wrong, $wrong]],
                ],
            ],
            'negative' => [
                'filter' => [
                    'value' => [[$good, $wrong], [$wrong, $wrong]],
                    'key' => [[$wrong, $good], [$wrong, $wrong]],
                    'both' => [[$wrong, $wrong]],
                    'any' => [[$good, $wrong], [$wrong, $good], [$wrong, $wrong]],
                ],
                'omit' => [
                    'value' => [[$wrong, $good], [$good, $good]],
                    'key' => [[$good, $wrong], [$good, $good]],
                    'both' => [[$good, $wrong], [$wrong, $good], [$good, $good]],
                    'any' => [[$good, $good]],
                ],
            ],
        ];
    }
    
    private function prepareFiltersForTest(Filter $filter): array
    {
        $value = $filter->checkValue();
        $key = $value->checkKey();
        $both = $key->checkBoth();
        $any = $both->checkAny();
        
        $notValue = Filters::NOT($filter)->checkValue();
        $notKey = $notValue->checkKey();
        $notBoth = $notKey->checkBoth();
        $notAny = $notBoth->checkAny();
        
        return [
            'positive' => ['value' => $value, 'key' => $key, 'both' => $both, 'any' => $any],
            'negative' => ['value' => $notValue, 'key' => $notKey, 'both' => $notBoth, 'any' => $notAny],
        ];
    }
    
    /**
     * @param mixed $good
     * @param mixed $wrong
     */
    private function prepareProducerForTest($good, $wrong): Producer
    {
        //[[$good, $wrong], [$wrong, $good], [$good, $good], [$wrong, $wrong]]
        return Producers::combinedFrom([$good, $wrong, $good, $wrong], [$wrong, $good, $good, $wrong]);
    }
    
    public function test_filter_by_zero_arg_callable_filter_with_constant_responses(): void
    {
        $data = ['a', 3, 'b', 2, '5'];
        
        self::assertSame($data, Stream::from($data)->filter(static fn(): bool => true)->toArrayAssoc());
    }
    
    public function test_omit_by_zero_arg_callable_filter_with_constant_responses(): void
    {
        self::assertSame([], Stream::from(['a', 3, 'b', 2, '5'])->omit(static fn(): bool => true)->toArrayAssoc());
    }
    
    public function test_filter_by_zero_arg_callable_filter_with_changing_responses(): void
    {
        $responses = [true, true, false, true];
        
        $actual = Stream::from([1, 2, 3, 4, 5])
            ->filter(static function () use (&$responses) {
                return empty($responses) ? false : \array_shift($responses);
            })
            ->toArrayAssoc();
        
        self::assertSame([1, 2, 3 => 4], $actual);
    }
    
    public function test_omit_by_zero_arg_callable_filter_with_changing_responses(): void
    {
        $responses = [true, true, false, true];
        
        $actual = Stream::from([1, 2, 3, 4, 5])
            ->omit(static function () use (&$responses) {
                return empty($responses) ? false : \array_shift($responses);
            })
            ->toArrayAssoc();
        
        self::assertSame([2 => 3, 4 => 5], $actual);
    }
    
    public function test_filter_by_negation_of_zero_arg_callable_filter_with_changing_responses(): void
    {
        $responses = [true, true, false, true];
        
        $actual = Stream::from([1, 2, 3, 4, 5])
            ->filter(Filters::NOT(static function () use (&$responses) {
                return empty($responses) ? false : \array_shift($responses);
            }))
            ->toArrayAssoc();
        
        self::assertSame([2 => 3, 4 => 5], $actual);
    }
    
    public function test_omit_by_negation_of_zero_arg_callable_filter_with_changing_responses(): void
    {
        $responses = [true, true, false, true];
        
        $actual = Stream::from([1, 2, 3, 4, 5])
            ->omit(Filters::NOT(static function () use (&$responses) {
                return empty($responses) ? false : \array_shift($responses);
            }))
            ->toArrayAssoc();
        
        self::assertSame([1, 2, 3 => 4], $actual);
    }
    
    public function test_filter_by_two_args_callable_filter(): void
    {
        $actual = Stream::from([0 => 1, 1 => 2, 2 => 3, 3 => 1])
            ->filter(static fn($val, $key): bool => $val === 1 || $key === 1)
            ->toArrayAssoc();
        
        self::assertSame([0 => 1, 1 => 2, 3 => 1], $actual);
    }
    
    public function test_omit_by_two_args_callable_filter(): void
    {
        $actual = Stream::from([0 => 1, 1 => 2, 2 => 3, 3 => 1])
            ->omit(static fn($val, $key): bool => $val === 1 || $key === 1)
            ->toArrayAssoc();
        
        self::assertSame([2 => 3], $actual);
    }
    
    public function test_filter_by_negation_of_two_args_callable_filter(): void
    {
        $actual = Stream::from([0 => 1, 1 => 2, 2 => 3, 3 => 1])
            ->filter(Filters::NOT(static fn($val, $key): bool => $val === 1 || $key === 1))
            ->toArrayAssoc();
        
        self::assertSame([2 => 3], $actual);
    }
    
    public function test_omit_by_negation_of_two_args_callable_filter(): void
    {
        $actual = Stream::from([0 => 1, 1 => 2, 2 => 3, 3 => 1])
            ->omit(Filters::NOT(static fn($val, $key): bool => $val === 1 || $key === 1))
            ->toArrayAssoc();
        
        self::assertSame([0 => 1, 1 => 2, 3 => 1], $actual);
    }
    
    public function test_use_callable_as_intValueRefs(): void
    {
        $result = Stream::from([3, 'a', 'b', 'c', 2, 'd', 'e', 1, 'f', 2, 'g', 'h', 1, 'i'])
            ->onlyIntegers()
            ->putIn($number)
            ->readNext(static function () use (&$number): int {
                return $number;
            })
            ->toArrayAssoc();
        
        self::assertSame([3 => 'c', 6 => 'e', 8 => 'f', 11 => 'h', 13 => 'i'], $result);
    }
    
    public function test_readNext_can_handle_volatile_integer_providers(): void
    {
        $one = 1;
        $result = [];
        
        Stream::from([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15])
            ->storeIn($result, true)
            ->readNext($one)
            ->readNext(static fn(): int => 1)
            ->readNext(IntNum::readFrom($one))
            ->storeIn($result, true)
            ->run();
            
        self::assertSame([1, 4, 5, 8, 9, 12, 13], $result);
    }
    
    public function test_readNext_throws_exception_when_current_howMany_is_invalid(): void
    {
        $intProvider = static fn(): int => -1;
        
        $this->expectExceptionObject(
            WrongIntValueException::invalidNumber(IntNum::getAdapter($intProvider))
        );
        
        Stream::from([1, 2, 3])->onlyIntegers()->readNext($intProvider)->run();
    }
    
    /**
     * @dataProvider getDataForTestReadManyThrowsExceptionWhenCurrentHowManyIsInvalid
     */
    #[DataProvider('getDataForTestReadManyThrowsExceptionWhenCurrentHowManyIsInvalid')]
    public function test_readMany_throws_exception_when_current_howMany_is_invalid(bool $reindex): void
    {
        $howMany = static fn(): int => -1;
        
        $this->expectExceptionObject(WrongIntValueException::invalidNumber(IntNum::getAdapter($howMany)));
        
        Stream::from([1, 2, 3, 4, 5])
            ->onlyIntegers()
            ->readMany($howMany, $reindex)
            ->run();
    }
    
    public static function getDataForTestReadManyThrowsExceptionWhenCurrentHowManyIsInvalid(): array
    {
        return [[true], [false]];
    }
    
    public function test_exception_is_thrown_when_quantity_of_consecutive_numbers_is_insufficient(): void
    {
        $this->expectExceptionObject(WrongIntValueException::noMoreIntegersToIterateOver());
        
        $data = [
            '1', '2',
            '---', 'a', 'b', 'c', 'd',
            '---', 'e', 'f', 'g', 'h',
            '---', 'i', 'j', 'k', 'l',
            '---', 'm', 'n', 'o', 'p',
        ];
        
        Stream::from($data)->filter('---')->readMany([2, 1, 0], true)->toArray();
    }
    
    public function test_readMany_does_not_read_next_values_when_the_number_of_readings_is_0(): void
    {
        $data = [
            '1', '2',
            '---', 'a', 'b', 'c', 'd',
            '---', 'e', 'f', 'g', 'h',
            '---', 'i', 'j', 'k', 'l',
            '---', 'm', 'n', 'o', 'p',
        ];
        
        self::assertSame(
            ['a', 'b', 'e', 'm', 'n'],
            Stream::from($data)->filter('---')->readMany(IntNum::infinitely([2, 1, 0]))->toArray()
        );
    }
    
    public function test_array_of_numbers_can_be_used_as_IntProvider_for_consecutive_readings(): void
    {
        $data = [
            '1', '2',
            '---', 'a', 'b', 'c', 'd',
            '---', 'e', 'f', 'g', 'h',
            '---', 'i', 'j', 'k', 'l',
            '---', 'm', 'n', 'o', 'p',
        ];
        
        self::assertSame(
            ['a', 'b', 'e', 'm', 'n'],
            Stream::from($data)->filter('---')->readMany([2, 1, 0, 2])->toArray()
        );
    }
    
    public function test_InfiniteIterator_can_be_used_as_IntProvider_for_consecutive_readings(): void
    {
        $data = [
            '1', '2',
            '---', 'a', 'b', 'c', 'd',
            '---', 'e', 'f', 'g', 'h',
            '---', 'i', 'j', 'k', 'l',
            '---', 'm', 'n', 'o', 'p',
        ];
        
        $result = Stream::from($data)
            ->filter('---')
            ->readMany(new \InfiniteIterator(new \ArrayIterator([2, 1, 0])))
            ->toArray();
        
        self::assertSame(['a', 'b', 'e', 'm', 'n'], $result);
    }
    
    public function test_Iterator_can_be_used_as_IntProvider_for_consecutive_readings_1(): void
    {
        $data = [
            '1', '2',
            '---', 'a', 'b', 'c', 'd',
            '---', 'e', 'f', 'g', 'h',
            '---', 'i', 'j', 'k', 'l',
            '---', 'm', 'n', 'o', 'p',
        ];
        
        self::assertSame(
            ['a', 'b', 'e', 'm', 'n'],
            Stream::from($data)->filter('---')->readMany(new \ArrayIterator([2, 1, 0, 2]))->toArray()
        );
    }
    
    public function test_Iterator_can_be_used_as_IntProvider_for_consecutive_readings_2(): void
    {
        $data = [
            '1', '2',
            '---', 'a', 'b', 'c', 'd',
            '---', 'e', 'f', 'g', 'h',
            '---', 'i', 'j', 'k', 'l',
            '---', 'm', 'n', 'o', 'p',
        ];
        
        $result = Stream::from($data)
            ->filter('---')
            ->readMany(IntNum::infinitely(new \ArrayIterator([2, 1, 0])))
            ->toArray();
        
        self::assertSame(['a', 'b', 'e', 'm', 'n'], $result);
    }
    
    public function test_consecutive_call(): void
    {
        $data = ['a', 'bbb', 'ccccc', 'ddddddd', 'eeeeee', 'ffff', 'gg'];
        
        $concatenator = Reducers::concat();
        $shortest = Reducers::shortest();
        $longest = Reducers::longest();
        $counter = Consumers::counter();
        $buffer = [];
        
        $collector = static function (string $value) use (&$buffer) {
            $buffer[] = $value;
        };
        
        Stream::from($data)
            ->call($concatenator)
            ->call($shortest, $longest)
            ->call(Consumers::idle())
            ->call($collector, $counter)
            ->run();
        
        self::assertSame(\implode('', $data), $concatenator->result());
        self::assertSame($data, $buffer);
        self::assertSame('a', $shortest->result());
        self::assertSame('ddddddd', $longest->result());
        self::assertSame(\count($data), $counter->get());
    }
    
    public function test_filter_stream_with_various_filters_and_unique_between_them(): void
    {
        $data = [
            'a', 1, 'b', -1, 2, 'c', -2, 3, 'd', 4, -3, 'e', 2, 5, -2, 'f', 3, 6, -5, 'g', 1, 7, -2, 'h', 8, 'i', 5,
        ];
        
        $result = Stream::from($data)
            ->onlyIntegers()
            ->greaterThan(0)
            ->unique()
            ->filter(Filters::number()->between(2, 6))
            ->toArray();
        
        self::assertSame([2, 3, 4, 5, 6], $result);
    }
    
    public function test_readNext_does_nothing_when_number_of_values_to_read_is_zero(): void
    {
        $readNext = static function (): int {
            static $skip = 0;
            $skip = $skip === 0 ? 1 : 0;
            return $skip;
        };
        
        $result = Stream::from([1, 'a', 'b', 2, 'c', 'd', 3, 'e', 'f', 4, 'g', 'h'])
            ->onlyStrings()
            ->readNext($readNext)
            ->toArray();
        
        self::assertSame(['b', 'c', 3, 'e', 4, 'g'], $result);
    }
    
    public function test_gather_on_empty_stream_keep_keys(): void
    {
        self::assertSame('', Stream::empty()->gather()->toString());
    }
    
    public function test_gather_on_empty_stream_reindex_keys(): void
    {
        self::assertSame('', Stream::empty()->gather(true)->toString());
    }
    
    public function test_reverse_on_empty_stream(): void
    {
        self::assertSame('', Stream::empty()->reverse()->toString());
    }
    
    public function test_shuffle_on_empty_stream(): void
    {
        self::assertSame('', Stream::empty()->shuffle()->toString());
    }
    
    public function test_map_reverse_strings(): void
    {
        self::assertSame(
            ['ytrewq', 'poiu', 'lkjhgfdsa'],
            Stream::from(['qwerty', 'uiop', 'asdfghjkl'])->map(Mappers::reverse())->toArray()
        );
    }
    
    public function test_map_reverse_throws_exception_on_invalid_value(): void
    {
        $this->expectExceptionObject(MapperExceptionFactory::unableToReverse(15));
        
        Stream::from(['aaaa', ['a', 'b', 'c'], 15])->map(Mappers::reverse())->run();
    }
    
    public function test_map_shuffle_array_values(): void
    {
        $item = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k'];
        
        self::assertNotSame($item, Stream::from([$item])->map(Mappers::shuffle())->first()->get());
    }
    
    public function test_map_shuffle_string_values(): void
    {
        $item = 'abcdefghijklmnop';
        
        self::assertNotSame($item, Stream::from([$item])->map(Mappers::shuffle())->first()->get());
    }
    
    public function test_map_shuffle_Iterator_values(): void
    {
        $item = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k'];
        
        self::assertNotSame($item, Stream::from([new \ArrayIterator($item)])->map(Mappers::shuffle())->first()->get());
    }
    
    public function test_map_shuffle_with_element_which_cannot_be_shuffled_returns_such_element(): void
    {
        $data = [6, 2, 8, 4, 6];
        
        self::assertSame($data, Stream::from($data)->map(Mappers::shuffle())->toArrayAssoc());
    }
}