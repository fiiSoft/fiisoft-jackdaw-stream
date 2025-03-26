<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Collector\Collectors;
use FiiSoft\Jackdaw\Comparator\Comparators;
use FiiSoft\Jackdaw\Comparator\Comparison\Compare;
use FiiSoft\Jackdaw\Comparator\Comparison\Comparison;
use FiiSoft\Jackdaw\Comparator\Sorting\By;
use FiiSoft\Jackdaw\Consumer\Consumers;
use FiiSoft\Jackdaw\Discriminator\Discriminators;
use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Exception\StreamExceptionFactory;
use FiiSoft\Jackdaw\Exception\UnsupportedValueException;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Filter\IdleFilter;
use FiiSoft\Jackdaw\Filter\Time\Day;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Mapper\Mappers;
use FiiSoft\Jackdaw\Memo\Memo;
use FiiSoft\Jackdaw\Operation\Exception\OperationExceptionFactory;
use FiiSoft\Jackdaw\Producer\Producer;
use FiiSoft\Jackdaw\Producer\Producers;
use FiiSoft\Jackdaw\Reducer\Reducers;
use FiiSoft\Jackdaw\Registry\Registry;
use FiiSoft\Jackdaw\Stream;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class StreamCTest extends TestCase
{
    private const ROWSET = [
        ['id' => 2, 'name' => 'Sue', 'age' => 22],
        ['id' => 9, 'name' => 'Chris', 'age' => 17],
        ['id' => 6, 'name' => 'Joanna', 'age' => 15],
        ['id' => 5, 'name' => 'Chris', 'age' => 24],
        ['id' => 7, 'name' => 'Sue', 'age' => 18],
    ];
    
    /**
     * @dataProvider getDataForTestSortByFieldsInVariousWays
     */
    #[DataProvider('getDataForTestSortByFieldsInVariousWays')]
    public function test_sort_by_fields_in_various_ways(Stream $example): void
    {
        self::assertSame([5, 9, 6, 2, 7], $example->extract('id')->toArray());
    }
    
    public static function getDataForTestSortByFieldsInVariousWays(): array
    {
        $rowset = Producers::getAdapter(self::ROWSET);
        
        $fields = ['name', 'age desc'];
        $reversed = ['name desc', 'age'];
        
        return [
            //ascending (normal) order
            
            [$rowset->stream()->sortBy(...$fields)],
            
            [$rowset->stream()->sort(By::fields($fields))],
            [$rowset->stream()->sort(By::fieldsAsc($fields))],
            
            [$rowset->stream()->sort(Comparators::fields($fields))],
            
            [$rowset->stream()->sort(By::value(Comparators::fields($fields)))],
            [$rowset->stream()->sort(By::valueAsc(Comparators::fields($fields)))],
            
            //double reversed order, so expected result is the same
            
            [$rowset->stream()->sort(By::fields($reversed, true))],
            [$rowset->stream()->rsort(By::fields($reversed))],
            [$rowset->stream()->rsort(By::fieldsDesc($fields))],
            
            [$rowset->stream()->rsort(Comparators::fields($reversed))],
            
            [$rowset->stream()->sort(By::valueDesc(Comparators::fields($reversed)))],
            [$rowset->stream()->rsort(By::valueDesc(Comparators::fields($fields)))],
            [$rowset->stream()->rsort(By::valueAsc(Comparators::fields($reversed)))],
        ];
    }
    
    public function test_sort_by_fields_asc(): void
    {
        self::assertSame(
            [5, 9, 6, 2, 7],
            Stream::from(self::ROWSET)->sort(By::fieldsAsc(['name', 'age desc']))->extract('id')->toArray()
        );
    }
    
    public function test_sort_by_fields_desc(): void
    {
        self::assertSame(
            [7, 2, 6, 9, 5],
            Stream::from(self::ROWSET)->sort(By::fieldsDesc(['name', 'age desc']))->extract('id')->toArray()
        );
    }
    
    public function test_sort_by_length_asc(): void
    {
        self::assertSame(
            ['sud', 'tsgad', 'ytbebafdof'],
            Stream::from(['tsgad', 'ytbebafdof', 'sud'])->sort(By::lengthAsc())->toArray()
        );
    }
    
    public function test_sort_by_length_desc(): void
    {
        self::assertSame(
            ['ytbebafdof', 'tsgad', 'sud'],
            Stream::from(['tsgad', 'ytbebafdof', 'sud'])->sort(By::lengthDesc())->toArray()
        );
    }
    
    public function test_sort_by_size_asc(): void
    {
        self::assertSame(
            [[5], [3,1], [4,2,8]],
            Stream::from([[5], [4,2,8], [3,1]])->sort(By::sizeAsc())->toArray()
        );
    }
    
    public function test_sort_by_size_desc(): void
    {
        self::assertSame(
            [[4,2,8], [3,1], [5]],
            Stream::from([[5], [4,2,8], [3,1]])->sort(By::sizeDesc())->toArray()
        );
    }
    
    public function test_use_Traversable_as_source_of_values_for_mapper(): void
    {
        self::assertSame(
            [5, 4, 3, 2, 'e'],
            Stream::from(['a', 'b', 'c', 'd', 'e'])->map(new \ArrayIterator([5, 4, 3, 2]))->toArray()
        );
    }
    
    public function test_use_Result_as_source_of_values_for_mapper(): void
    {
        $result = Stream::from(['a', 'b', 'c', 'd', 'e'])
            ->moveTo('char')
            ->append('number', Stream::from([5, 4, 3, 2])->collect())
            ->mapFieldWhen('number', 'is_array', Mappers::extract('char'))
            ->toArray();
        
        self::assertSame([
            ['char' => 'a', 'number' => 5],
            ['char' => 'b', 'number' => 4],
            ['char' => 'c', 'number' => 3],
            ['char' => 'd', 'number' => 2],
            ['char' => 'e', 'number' => 'e'],
        ], $result);
    }
    
    public function test_use_Producer_as_source_of_values_for_mapper(): void
    {
        $expected = [
            ['id' => 2, 'name' => 'Sue', 'age' => 8],
            ['id' => 9, 'name' => 'Chris', 'age' => 4],
            ['id' => 6, 'name' => 'Joanna', 'age' => 2],
            ['id' => 5, 'name' => 'Chris', 'age' => 1],
            ['id' => 7, 'name' => 'Sue', 'age' => 18],
        ];
        
        self::assertSame($expected, Stream::from(self::ROWSET)->mapField('age', Producers::collatz(8))->toArray());
    }
    
    public function test_use_Generator_as_source_of_values_for_mapper(): void
    {
        $words = static function () {
            yield from ['this', 'is', 'it'];
        };
        
        self::assertSame(
            ['this' => 1, 'is' => 2, 'it' => 3, 'd' => 4],
            Stream::from(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4])->mapKey($words())->toArrayAssoc()
        );
    }
    
    public function test_use_the_same_Generator_as_Mapper_for_values_and_keys(): void
    {
        $words = static function () {
            yield from ['this', 'is', 'it'];
        };
        
        self::assertSame(
            ['this' => 'this', 'is' => 'is', 'it' => 'it', 'd' => 4],
            Stream::from(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4])->mapKey($words())->map($words())->toArrayAssoc()
        );
    }
    
    public function test_use_Stream_as_Mapper_for_values_and_keys(): void
    {
        $collatz = Producers::collatz(100);
        
        $result = Stream::from(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4])
            ->mapKey($collatz->stream()->limit(3))
            ->map($collatz->stream()->limit(3))
            ->toArrayAssoc();
        
        self::assertSame([100 => 100, 50 => 50, 25 => 25, 'd' => 4], $result);
    }
    
    public function test_use_the_same_Producer_as_Mapper_for_values_and_keys(): void
    {
        $numbers = Producers::sequentialInt(1, 1, 3);
        
        $result = Stream::from(['a' => 'a', 'b' => 'b', 'c' => 'c', 'd' => 'd'])
            ->mapKey($numbers)
            ->map($numbers)
            ->toArrayAssoc();
        
        self::assertSame([1 => 1, 2 => 2, 3 => 3, 'd' => 'd'], $result);
    }
    
    /**
     * @dataProvider getDataForTestUniqueThoroughly
     */
    #[DataProvider('getDataForTestUniqueThoroughly')]
    public function test_Unique_compare_values_by_default_comparator_in_various_ways(array $data, array $expected): void
    {
        self::assertSame($expected, Stream::from($data)->unique()->toArray());
        self::assertSame($expected, Stream::from($data)->unique(Compare::values())->toArray());
        self::assertSame($expected, Stream::from($data)->unique(Comparators::default())->toArray());
        self::assertSame($expected, Stream::from($data)->unique(Compare::values(Comparators::default()))->toArray());
        self::assertSame($expected, Stream::from($data)->unique(By::value())->toArray());
        self::assertSame($expected, Stream::from($data)->unique(By::value(Comparators::default()))->toArray());
    }
    
    /**
     * @dataProvider getDataForTestUniqueThoroughly
     */
    #[DataProvider('getDataForTestUniqueThoroughly')]
    public function test_Unique_compare_values_by_custom_comparator_in_various_ways(array $data, array $expected): void
    {
        $comparator = static fn($a, $b): int => \gettype($a) <=> \gettype($b) ?: $a <=> $b;
        
        $comparisons = [
            $comparator,
            Compare::values($comparator),
            Comparators::getAdapter($comparator),
            By::value($comparator),
            Compare::values(Comparators::getAdapter($comparator)),
            By::value(Comparators::getAdapter($comparator)),
        ];
        
        foreach ($comparisons as $comparison) {
            self::assertSame($expected, Stream::from($data)->unique($comparison)->toArray());
        }
    }
    
    public static function getDataForTestUniqueThoroughly(): array
    {
        return [
            //data, expected
            [[], []],
            [[1], [1]],
            [[1, 2, 1, 2], [1, 2]],
            [[2, 1, 2, 1], [2, 1]],
            [[3, 3, 3], [3]],
            [[1, 2, 3, 3, 2, 1], [1, 2, 3]],
            [[3, 2, 1, 1, 2, 3], [3, 2, 1]],
            [[6, 5, 4, 1, 2, 3], [6, 5, 4, 1, 2, 3]],
            [[6, 5, 4, 6, 5, 4, 1, 2, 3, 1, 2, 3], [6, 5, 4, 1, 2, 3]],
            [
                ['a', 0, '', 1, false, 'b', 2, true, 0, '', 'a', 3, 'b', 2, '1', 1, 'c', 2, '3', 2],
                ['a', 0, '', 1, false, 'b', 2, true, 3, '1', 'c', '3']
            ],
        ];
    }
    
    public function test_Sort_by_both_asc(): void
    {
        $keys =   [0,  'b', 2, 1,   'a', 1,  'b',   2,    'c'];
        $values = ['a', 3,  2, 'a', 'b', 'b', true, true, 'a'];
        
        self::assertSame([
            [2, true],
            ['b', true],
            [2, 2],
            ['b', 3],
            [0, 'a'],
            [1, 'a'],
            ['c', 'a'],
            [1, 'b'],
            ['a', 'b'],
        ], Producers::combinedFrom($keys, $values)->stream()->sort(By::bothAsc())->makeTuple()->toArray());
    }
    
    public function test_Sort_by_both_desc(): void
    {
        $keys =   [0,  'b', 2, 1,   'a', 1,  'b',   2,    'c'];
        $values = ['a', 3,  2, 'a', 'b', 'b', true, true, 'a'];
        
        self::assertSame([
            ['a', 'b'],
            [1, 'b'],
            ['c', 'a'],
            [1, 'a'],
            [0, 'a'],
            ['b', 3],
            [2, 2],
            ['b', true],
            [2, true],
        ], Producers::combinedFrom($keys, $values)->stream()->sort(By::bothDesc())->makeTuple()->toArray());
    }
    
    public function test_collatz_with_variable_as_source_of_stream(): void
    {
        $collector = Reducers::concat(', ');
        $current = 304;
        
        Stream::from(Producers::readFrom($current))
            ->callOnce($collector)
            ->mapWhen(
                static fn(int $n): bool => ($n & 1) === 0,
                static fn(int $n): int => $n >> 1,
                static fn(int $n): int => (3 * $n + 1)
            )
            ->call($collector)
            ->until(1)
            ->putIn($current)
            ->run();
        
        $expected = '304, 152, 76, 38, 19, 58, 29, 88, 44, 22, 11, 34, 17, 52, 26, 13, 40, 20, 10, 5, 16, 8, 4, 2, 1';
        
        self::assertSame($expected, $collector->result());
    }
    
    public function test_collatz_with_Registry_as_source_of_stream(): void
    {
        $collector = Reducers::concat(', ');
        $entry = Registry::new()->entry(Check::VALUE, 304);
        
        Stream::from($entry)
            ->callOnce($collector)
            ->mapWhen(
                static fn(int $n): bool => ($n & 1) === 0,
                static fn(int $n): int => $n >> 1,
                static fn(int $n): int => (3 * $n + 1)
            )
            ->call($collector)
            ->until(1)
            ->remember($entry)
            ->run();
        
        $expected = '304, 152, 76, 38, 19, 58, 29, 88, 44, 22, 11, 34, 17, 52, 26, 13, 40, 20, 10, 5, 16, 8, 4, 2, 1';
        
        self::assertSame($expected, $collector->result());
    }
    
    public function test_collatz_with_Entry_as_source_of_stream(): void
    {
        $collector = Reducers::concat(', ');
        $value = Memo::value(304);
        
        Stream::from($value)
            ->callOnce($collector)
            ->mapWhen(
                static fn(int $n): bool => ($n & 1) === 0,
                static fn(int $n): int => $n >> 1,
                static fn(int $n): int => (3 * $n + 1)
            )
            ->call($collector)
            ->until(1)
            ->remember($value)
            ->run();
        
        $expected = '304, 152, 76, 38, 19, 58, 29, 88, 44, 22, 11, 34, 17, 52, 26, 13, 40, 20, 10, 5, 16, 8, 4, 2, 1';
        
        self::assertSame($expected, $collector->result());
    }
    
    public function test_fizzbuzz_with_variable_as_source_of_stream(): void
    {
        $counter = 1;
        
        $result = Stream::from(Producers::readFrom($counter))
            ->while(Filters::lessOrEqual(30))
            ->mapKey(static fn(int $n): int => ($n % 3 === 0 ? 2 : 0) | ($n % 5 === 0 ? 1 : 0))
            ->map(static fn(int $n, int $k): string => (string) [$n, 'Buzz', 'Fizz', 'Fizz Buzz'][$k])
            ->countIn($counter)
            ->reduce(Reducers::concat(', '));
        
        $expected = '1, 2, Fizz, 4, Buzz, Fizz, 7, 8, Fizz, Buzz, 11, Fizz, 13, 14, Fizz Buzz, 16, 17, Fizz, 19, '
            .'Buzz, Fizz, 22, 23, Fizz, Buzz, 26, Fizz, 28, 29, Fizz Buzz';
        
        self::assertSame($expected, $result->get());
    }
    
    public function test_fizzbuzz_with_Registry_as_source_of_stream(): void
    {
        $counter = Registry::new()->entry(Check::VALUE, 1);
        
        $result = Stream::from($counter)
            ->while(Filters::lessOrEqual(30))
            ->call(static function (int $val) use ($counter) {
                $counter->set($val + 1);
            })
            ->mapKey(static fn(int $n): int => ($n % 3 === 0 ? 2 : 0) | ($n % 5 === 0 ? 1 : 0))
            ->map(static fn(int $n, int $k): string => (string) [$n, 'Buzz', 'Fizz', 'Fizz Buzz'][$k])
            ->reduce(Reducers::concat(', '));
        
        $expected = '1, 2, Fizz, 4, Buzz, Fizz, 7, 8, Fizz, Buzz, 11, Fizz, 13, 14, Fizz Buzz, 16, 17, Fizz, 19, '
            .'Buzz, Fizz, 22, 23, Fizz, Buzz, 26, Fizz, 28, 29, Fizz Buzz';
        
        self::assertSame($expected, $result->get());
    }
    
    public function test_use_Registry_as_Consumer(): void
    {
        $lastNumber = Registry::shared()->entry(Check::BOTH);
        $lastLetter = Registry::shared()->entry(Check::BOTH);
        
        Stream::from(['a', 7, 3, 'b', false, 'c', 5, 'd'])
            ->while(Filters::OR('is_int', 'is_string'))
            ->callWhen('is_int', $lastNumber)
            ->callWhen('is_string', $lastLetter)
            ->run();
        
        self::assertSame([2, 3], $lastNumber->get());
        self::assertSame([3, 'b'], $lastLetter->get());
    }
    
    public function test_use_Registry_as_Discriminator(): void
    {
        //given
        $discriminator = Registry::new()->entry(Check::VALUE);
        
        //when
        $result = Stream::from([9, 6, 8, 7, 6, 4, 7, 5, 2, 5])
            ->callOnce(static function (int $firstValue) use ($discriminator) {
                $discriminator->set($firstValue > 5 ? 'foo' : 'bar');
            })
            ->callWhen(
                Filters::AND(Filters::lessOrEqual(5), static fn(): bool => $discriminator->get() !== 'bar'),
                static function () use ($discriminator) {
                    $discriminator->set('bar');
                }
            )
            ->fork($discriminator, Reducers::sum())
            ->toArrayAssoc();
        
        //then
        self::assertSame([
            'foo' => 9 + 6 + 8 + 7 + 6,
            'bar' => 4 + 7 + 5 + 2 + 5,
        ], $result);
    }
    
    public function test_generate_decreasing_trend_with_help_of_Registry(): void
    {
        $threshold = Registry::new()->entry(Check::VALUE);
        
        $result = Stream::from([6, 5, 8, 5, 2, 3, 5, 2, 7, 1, 3, 5, 1, 2, 2, 5, 3, 5])
            ->callOnce($threshold)
            ->filter(static fn(int $v): bool => $v <= $threshold->get())
            ->remember($threshold);
        
        self::assertSame([6, 5, 5, 2, 2, 1, 1], $result->toArray());
    }
    
    public function test_generate_decreasing_trend_with_help_of_Memo(): void
    {
        $threshold = Memo::value();
        
        $result = Stream::from([6, 5, 8, 5, 2, 3, 5, 2, 7, 1, 3, 5, 1, 2, 2, 5, 3, 5])
            ->callOnce($threshold)
            ->filter(static fn(int $v): bool => $v <= $threshold->read())
            ->remember($threshold);
        
        self::assertSame([6, 5, 5, 2, 2, 1, 1], $result->toArray());
    }
    
    public function test_filter_while(): void
    {
        $result = Stream::from([4, -5, 2, -1, 3, -3, 'a', 3, -2, 4, 'b', -3])
            ->filterWhile('is_int', Filters::greaterThan(0))
            ->toArray();
        
        self::assertSame([4, 2, 3, 'a', 3, -2, 4, 'b', -3], $result);
    }
    
    public function test_filter_until(): void
    {
        $result = Stream::from([4, -5, 2, -1, 3, -3, 'a', 3, -2, 4, 'b', -3])
            ->filterUntil('is_string', Filters::greaterThan(0))
            ->toArray();
        
        self::assertSame([4, 2, 3, 'a', 3, -2, 4, 'b', -3], $result);
    }
    
    public function test_map_while(): void
    {
        $result = Stream::from([4, -5, 2, -1, 3, -3, 'a', 3, -2, 4, 'b', -3])
            ->mapWhile('is_int', static fn(int $v): int => $v * 2)
            ->toArray();
        
        self::assertSame([8, -10, 4, -2, 6, -6, 'a', 3, -2, 4, 'b', -3], $result);
    }
    
    public function test_map_until(): void
    {
        $result = Stream::from([4, -5, 2, -1, 3, -3, 'a', 3, -2, 4, 'b', -3])
            ->mapUntil('is_string', static fn(int $v): int => $v * 2)
            ->toArray();
        
        self::assertSame([8, -10, 4, -2, 6, -6, 'a', 3, -2, 4, 'b', -3], $result);
    }
    
    public function test_call_while(): void
    {
        $countIntsAtTheBeginning = Consumers::counter();
        $countAllInts = Consumers::counter();
        
        Stream::from([4, -5, 2, -1, 3, -3, 'a', 3, -2, 4, 'b', -3])
            ->callWhile('is_int', $countIntsAtTheBeginning)
            ->callWhen('is_int', $countAllInts)
            ->run();
        
        self::assertSame(10, $countAllInts->get());
        self::assertSame(6, $countIntsAtTheBeginning->get());
    }
    
    public function test_call_until(): void
    {
        $countIntsAtTheBeginning = Consumers::counter();
        $countAllInts = Consumers::counter();
        
        Stream::from([4, -5, 2, -1, 3, -3, 'a', 3, -2, 4, 'b', -3])
            ->callUntil('is_string', $countIntsAtTheBeginning)
            ->callWhen('is_int', $countAllInts)
            ->run();
        
        self::assertSame(10, $countAllInts->get());
        self::assertSame(6, $countIntsAtTheBeginning->get());
    }
    
    /**
     * @dataProvider getDataForTestWindow
     */
    #[DataProvider('getDataForTestWindow')]
    public function test_window_with_reindex(int $size, int $step, $expected): void
    {
        if ($expected instanceof \Exception) {
            $this->expectExceptionObject($expected);
        }

        $result = Stream::from(['a', 'b', 'c', 'd', 'e', 'f', 'g'])->window($size, $step, true)->toArrayAssoc();

        self::assertSame($expected, $result);
    }

    public static function getDataForTestWindow(): array
    {
        return [
            //size, step, expected
            0 => [0, 1, InvalidParamException::byName('size')],
                [1, 0, InvalidParamException::byName('step')],
                [1, 1, [['a'], ['b'], ['c'], ['d'], ['e'], ['f'], ['g']]],
                [1, 2, [['a'], ['c'], ['e'], ['g']]],
                [1, 3, [['a'], ['d'], ['g']]],
            5 => [1, 4, [['a'], ['e']]],
                [1, 5, [['a'], ['f']]],
                [1, 6, [['a'], ['g']]],
                [1, 7, [['a']]],
                [2, 1, [['a', 'b'], ['b', 'c'], ['c', 'd'], ['d', 'e'], ['e', 'f'], ['f', 'g']]],
            10 => [2, 2, [['a', 'b'], ['c', 'd'], ['e', 'f'], ['g']]],
                [2, 3, [['a', 'b'], ['d', 'e'], ['g']]],
                [2, 4, [['a', 'b'], ['e', 'f']]],
                [2, 5, [['a', 'b'], ['f', 'g']]],
                [2, 6, [['a', 'b'], ['g']]],
            15 => [2, 7, [['a', 'b']]],
                [3, 1, [
                    ['a', 'b', 'c'], ['b', 'c', 'd'], ['c', 'd', 'e'], ['d', 'e', 'f'], ['e', 'f', 'g']
                ]],
                [3, 2, [['a', 'b', 'c'], ['c', 'd', 'e'], ['e', 'f', 'g']]],
                [3, 3, [['a', 'b', 'c'], ['d', 'e', 'f'], ['g']]],
                [3, 4, [['a', 'b', 'c'], ['e', 'f', 'g']]],
            20 => [3, 5, [['a', 'b', 'c'], ['f', 'g']]],
                [3, 6, [['a', 'b', 'c'], ['g']]],
                [4, 1, [['a', 'b', 'c', 'd'], ['b', 'c', 'd', 'e'], ['c', 'd', 'e', 'f'], ['d', 'e', 'f', 'g']]],
                [4, 2, [['a', 'b', 'c', 'd'], ['c', 'd', 'e', 'f'], ['e', 'f', 'g']]],
                [4, 3, [['a', 'b', 'c', 'd'], ['d', 'e', 'f', 'g']]],
            25 => [4, 4, [['a', 'b', 'c', 'd'], ['e', 'f', 'g']]],
                [4, 5, [['a', 'b', 'c', 'd'], ['f', 'g']]],
                [4, 6, [['a', 'b', 'c', 'd'], ['g']]],
                [4, 7, [['a', 'b', 'c', 'd']]],
                [7, 1, [['a', 'b', 'c', 'd', 'e', 'f', 'g']]],
            30 => [7, 2, [['a', 'b', 'c', 'd', 'e', 'f', 'g']]],
                [8, 1, [['a', 'b', 'c', 'd', 'e', 'f', 'g']]],
        ];
    }

    public function test_window_with_preserve_keys(): void
    {
        $expected = [
            [0 => 'a', 'b', 'c'],
            [2 => 'c', 'd', 'e'],
            [4 => 'e', 'f'],
        ];

        self::assertSame($expected, Stream::from(['a', 'b', 'c', 'd', 'e', 'f'])->window(3, 2)->toArrayAssoc());
    }
    
    public function test_fork_window(): void
    {
        $result = Stream::from(['a', 1, 'b', 2, 'c', 3, 4, 'd'])
            ->fork(
                Discriminators::yesNo('is_string', 'strings', 'integers'),
                Stream::empty()->window(2)->collect()
            )
            ->toArrayAssoc();
        
        self::assertSame([
            'strings' => [
                [0 => 'a', 2 => 'b'],
                [2 => 'b', 4 => 'c'],
                [4 => 'c', 7 => 'd'],
            ],
            'integers' => [
                [1 => 1, 3 => 2],
                [3 => 2, 5 => 3],
                [5 => 3, 6 => 4],
            ],
        ], $result);
    }
    
    /**
     * @dataProvider getDataForTestEveryNthElement
     */
    #[DataProvider('getDataForTestEveryNthElement')]
    public function test_everyNth_element(int $num, array $expected): void
    {
        self::assertSame($expected, Stream::from(['a', 'b', 'c', 'd'])->everyNth($num)->toArrayAssoc());
    }
    
    public static function getDataForTestEveryNthElement(): array
    {
        return [
            [1, ['a', 'b', 'c', 'd']],
            [2, ['a', 2 => 'c']],
            [3, ['a', 3 => 'd']],
            [4, ['a']],
        ];
    }
    
    public function test_between_check_key_in_three_ways(): void
    {
        $data = [7, 2, 5, 1, 9, 3, 6];
        $expected = [3 => 1, 4 => 9, 5 => 3];
        
        self::assertSame(
            $expected,
            Stream::from($data)->filter(Filters::number(Check::KEY)->between(3, 5))->toArrayAssoc()
        );
        
        self::assertSame(
            $expected,
            Stream::from($data)->filter(Filters::number()->between(3, 5), Check::KEY)->toArrayAssoc()
        );
        
        self::assertSame(
            $expected,
            Stream::from($data)->filter(Filters::number()->between(3, 5)->checkKey())->toArrayAssoc()
        );
    }
    
    public function test_stream_infinite_producer_with_limit_filter_unique_and_sort(): void
    {
        $infiniteProducer = static function () {
            $num = false;
            $index = $value = 0;
            
            while (true) {
                yield $index++ => $num ? $value++ : 'a';
                $num = !$num;
            }
        };
        
        $countIntegers = Stream::from($infiniteProducer)
            ->countIn($countIterations)
            ->onlyIntegers()
            ->unique()
            ->limit(20)
            ->rsort()
            ->count();
        
        self::assertSame(20, $countIntegers->get());
        self::assertSame(40, $countIterations);
    }
    
    public function test_dispatch_throws_exception_when_classifier_is_unknown(): void
    {
        $this->expectExceptionObject(OperationExceptionFactory::handlerIsNotDefined('bar'));
        
        Stream::from([1])
            ->dispatch(
                static fn(): string => 'bar',
                ['foo' => Reducers::count()]
            )
            ->run();
    }
    
    public function test_use_ArrayAccess_object_as_buffer_for_storeIn(): void
    {
        $buffer = new \ArrayObject();
        
        self::assertTrue(Stream::from([6, 2, 4, 1])->storeIn($buffer)->find(2, Check::KEY)->found());
        self::assertSame([6, 2, 4], $buffer->getArrayCopy());
    }
    
    public function test_map_value_using_variable(): void
    {
        $actual = Stream::from([4 => 'Joe', 2 => 'Cris', 5 => 'Helen'])
            ->call(Consumers::sendKeyTo($key))
            ->map(Mappers::readFrom($key))
            ->toArrayAssoc();
        
        self::assertSame([4 => 4, 2 => 2, 5 => 5], $actual);
    }
    
    public function test_map_key_using_variable(): void
    {
        $actual = Stream::from([4 => 'Joe', 2 => 'Cris', 5 => 'Helen'])
            ->call(Consumers::sendValueTo($value))
            ->mapKey(Mappers::readFrom($value))
            ->toArrayAssoc();
        
        self::assertSame(['Joe' => 'Joe', 'Cris' => 'Cris', 'Helen' => 'Helen'], $actual);
    }
    
    public function test_negated_filterBy_in_while(): void
    {
        $data = [
            ['id' => 4, 'name' => 'Joe'],
            ['id' => 5, 'name' => 'Cristine'],
        ];
        
        $expected = [['id' => 4, 'name' => 'Joe']];
        
        self::assertSame(
            $expected,
            Stream::from($data)->while(Filters::filterBy('name', Filters::length()->le(5)))->toArray()
        );
        
        self::assertSame(
            $expected,
            Stream::from($data)->until(Filters::NOT(Filters::filterBy('name', Filters::length()->le(5))))->toArray()
        );
        
        self::assertSame(
            $expected,
            Stream::from($data)->until(Filters::filterBy('name', Filters::length()->le(5))->negate())->toArray()
        );
    }
    
    public function test_filter_by_string_length(): void
    {
        self::assertSame(
            ['ooooo', 'gggg'],
            Stream::from(['ooooo', 'qq', 'gggg', 'nnn', 'a'])->filter(Filters::string()->length()->gt(3))->toArray()
        );
    }
    
    public function test_omit_by_string_length(): void
    {
        self::assertSame(
            ['qq', 'nnn', 'a'],
            Stream::from(['ooooo', 'qq', 'gggg', 'nnn', 'a'])->omit(Filters::string()->length()->gt(3))->toArray()
        );
    }
    
    public function test_filter_by_comparing_value_and_key_in_the_same_time(): void
    {
        self::assertSame(
            [5 => 'a', 4 => 'w'],
            Stream::from([5 => 'a', 'c' => 1, 3 => 8, 4 => 'w', 't' => 'n', 's' => 2])
                ->filter(Filters::AND(Filters::isInt(Check::KEY), Filters::isString(Check::VALUE)))
                ->toArrayAssoc()
        );
    }
    
    public function test_omit_by_comparing_value_and_key_in_the_same_time(): void
    {
        self::assertSame(
            ['c' => 1, 3 => 8, 't' => 'n', 's' => 2],
            Stream::from([5 => 'a', 'c' => 1, 3 => 8, 4 => 'w', 't' => 'n', 's' => 2])
                ->omit(Filters::AND(Filters::isInt(Check::KEY), Filters::isString(Check::VALUE)))
                ->toArrayAssoc()
        );
    }
    
    public function test_filter_by_comparing_value_and_key_in_any_mode(): void
    {
        self::assertSame(
            [5 => 'a', 3 => 8, 4 => 'w'],
            Stream::from([5 => 'a', 'c' => 1, 3 => 8, 4 => 'w', 't' => 'n', 's' => 2])
                ->filter(Filters::AND(Filters::isInt(), Filters::greaterOrEqual(3)), Check::ANY)
                ->toArrayAssoc()
        );
    }
    
    public function test_filter_by_length(): void
    {
        $lf = Filters::length();
        
        $actual = Stream::from(['b', 'foo', 2, 'zoo', 'bar', 1, 'a'])
            ->filter($lf->isString()->and($lf->lt(3)))
            ->toArray();
        
        self::assertSame(['b', 'a'], $actual);
    }
    
    public function test_filter_by_idle_filter_true(): void
    {
        $data = [3 => 'a', 'b', 'c'];
        
        self::assertSame($data, Stream::from($data)->filter(IdleFilter::true())->toArrayAssoc());
    }
    
    public function test_filter_by_idle_filter_false(): void
    {
        self::assertSame([], Stream::from([3 => 'a', 'b', 'c'])->filter(IdleFilter::false())->toArrayAssoc());
    }
    
    public function test_filter_by_complex_structure_built_using_Filter_methods(): void
    {
        //given
        $keys =   [0,  'b', 2, 1,   'a', 1,  'b',   'c', 3];
        $values = ['a', 3,  2, 'a', 'b', 'b', true, 'a', 1];
        
        //when
        $keyIsIntAndValIsStr = Filters::isInt()->checkKey()->and(Filters::isString()->checkValue());
        $keyIsStrAndValIsInt = Filters::isString(Check::KEY)->and(Filters::isInt(Check::VALUE));
        $keyAndValAreEqualInts = Filters::isInt(Check::BOTH)->and(fn(int $v, int $k): bool => $v === $k);
        
        $actual = Stream::from(Producers::combinedFrom($keys, $values))
            ->filter($keyIsIntAndValIsStr->or($keyIsStrAndValIsInt)->or($keyAndValAreEqualInts))
            ->makeTuple();
        
        //then
        self::assertSame([
            [0, 'a'],
            ['b', 3],
            [2, 2],
            [1, 'a'],
            [1, 'b'],
        ], $actual->toArray());
    }
    
    public function test_filter_stream_with_xor_filter(): void
    {
        //given
        $keys =   [0,  'b', 2, 1,   'a', 1,  'b',   'c', 3];
        $values = ['a', 3,  2, 'a', 'b', 'b', true, 'a', 1];
        
        //when
        $actual = Stream::from(Producers::combinedFrom($keys, $values))
            ->filter(Filters::OR('is_int', 'is_string'), Check::BOTH)
            ->filter(Filters::isInt(Check::VALUE)->xor(Filters::isInt(Check::KEY)))
            ->makeTuple();
        
        //then
        self::assertSame([
            [0, 'a'],
            ['b', 3],
            [1, 'a'],
            [1, 'b'],
        ], $actual->toArray());
    }
    
    public function test_filter_stream_with_xnor_filter(): void
    {
        //given
        $keys =   [0,  'b', 2, 1,   'a', 1,  'b',   'c', 3];
        $values = ['a', 3,  2, 'a', 'b', 'b', true, 'a', 1];
        
        //when
        $actual = Stream::from(Producers::combinedFrom($keys, $values))
            ->filter(Filters::OR('is_int', 'is_string'), Check::BOTH)
            ->filter(Filters::isInt(Check::VALUE)->xnor(Filters::isInt(Check::KEY)))
            ->makeTuple();
        
        //then
        self::assertSame([
            [2, 2],
            ['a', 'b'],
            ['c', 'a'],
            [3, 1],
        ], $actual->toArray());
    }
    
    public function test_filter_stream_with_andNot_filter(): void
    {
        //given
        $keys =   [0,  'b', 2, 1,   'a', 1,  'b',   'c', 3];
        $values = ['a', 3,  2, 'a', 'b', 'b', true, 'a', 1];
        
        //when
        $actual = Stream::from(Producers::combinedFrom($keys, $values))
            ->filter(Filters::isString(Check::KEY)->andNot(Filters::isBool(Check::VALUE)))
            ->makeTuple();
        
        //then
        self::assertSame([
            ['b', 3],
            ['a', 'b'],
            ['c', 'a'],
        ], $actual->toArray());
    }
    
    public function test_filter_stream_with_orNot_filter(): void
    {
        //given
        $keys =   [0,  'b', 2, 1,   'a', 1,  'b',   'c', 3];
        $values = ['a', 3,  2, 'a', 'b', 'b', true, 'a', 1];
        
        //when
        $actual = Stream::from(Producers::combinedFrom($keys, $values))
            ->filter(Filters::isString(Check::KEY)->orNot(Filters::isString(Check::VALUE)))
            ->makeTuple();
        
        //then
        self::assertSame([
            ['b', 3],
            [2, 2],
            ['a', 'b'],
            ['b', true],
            ['c', 'a'],
            [3, 1],
        ], $actual->toArray());
    }
    
    public function test_filter_both_with_xor(): void
    {
        //(v < 0 XOR isEven(v)) && (k < 0 XOR isEven(k))
        $actual = Stream::from([-5 => 3, 0 => 3, -1 => 4, 2 => 6, 1 => 2, -2 => 2, 4 => -3])
            ->filter(Filters::lessThan(0)->xor(Filters::number()->isEven()), Check::BOTH);
        
        self::assertSame([
            -1 => 4,
            2 => 6,
            4 => -3,
        ], $actual->toArrayAssoc());
    }
    
    public function test_filter_any_with_xor(): void
    {
        //(v < 1 XOR isOdd(v)) || (k < 1 XOR isOdd(k))
        $actual = Stream::from([-5 => 3, 0 => 3, -1 => 4, 2 => 6, 1 => 2, -2 => 2, 4 => -3])
            ->filter(Filters::lessThan(1)->xor(Filters::number()->isOdd()), Check::ANY);
        
        self::assertSame([
            -5 => 3,
            0 => 3,
            1 => 2,
            -2 => 2,
        ], $actual->toArrayAssoc());
    }
    
    public function test_filter_both_with_xnor(): void
    {
        //(v < 0 XNOR isEven(v)) && (k < 0 XNOR isEven(k))
        $actual = Stream::from([-4 => 3, -2 => 3, -1 => 4, 2 => 6, 1 => 3, 4 => -3])
            ->filter(Filters::lessThan(0)->xnor(Filters::number()->isEven()), Check::BOTH);
        
        self::assertSame([
            -4 => 3,
            -2 => 3,
            1 => 3,
        ], $actual->toArrayAssoc());
    }
    
    public function test_filter_any_with_xnor(): void
    {
        //(v < 1 XNOR isOdd(v)) || (k < 1 XNOR isOdd(k))
        $actual = Stream::from([-4 => 3, -2 => 2, -1 => 4, 2 => 6, 1 => 3, 4 => -3])
            ->filter(Filters::lessThan(1)->xnor(Filters::number()->isOdd()), Check::ANY);
        
        self::assertSame([
            -2 => 2,
            -1 => 4,
            2 => 6,
            4 => -3,
        ], $actual->toArrayAssoc());
    }
    
    public function test_filterByMany(): void
    {
        $rowset = [
            ['id' => 2, 'name' => 'Kate', 'age' => 35],
            ['id' => 9, 'name' => 'Chris', 'age' => 26],
            ['id' => 6, 'name' => 'Joanna', 'age' => 35],
            ['id' => 5, 'name' => 'Chris', 'age' => 15],
            ['id' => 7, 'name' => 'Sue', 'age' => 17],
            ['id' => 4, 'name' => 'Chris', 'age' => 22],
        ];
        
        $actual = Stream::from($rowset)
            ->filterBy('name', 'Chris')
            ->omitBy('age', Filters::number()->not()->ge(20))
            ->mapKey(Mappers::extract('id'))
            ->extract('age')
            ->toArrayAssoc();
        
        self::assertSame([9 => 26, 4 => 22], $actual);
    }
    
    public function test_reuse_producer_few_times(): void
    {
        $producer = Producers::sequentialInt(1, 2, 3);
        
        self::assertSame([1, 3, 5], $producer->stream()->toArray());
        self::assertSame([1, 3, 5], $producer->stream()->toArray());
        self::assertSame([1, 3, 5], $producer->stream()->toArray());
    }
    
    public function test_stream_can_wrap_producer(): void
    {
        $prototype = Stream::empty()->limit(5)->onlyIntegers();
        
        $sum = $prototype->wrap(['a', 1, 'b', 2, 'c', 3, 'd', 4])->reduce(Reducers::sum());
        self::assertSame(3, $sum->get());
        
        $result = $prototype->wrap([4, 'd', 3, 'c', 2, 'b', 1, 'a'])->reverse()->toArray();
        self::assertSame([2, 3, 4], $result);
        
        $find = $prototype->wrap([2, 'a', 7, 'b', 15, 'c', 4])->find(Filters::greaterThan(10));
        
        self::assertTrue($find->found());
        self::assertSame(15, $find->get());
        self::assertSame(4, $find->key());
    }
    
    public function test_attempt_to_wrap_producer_by_utilized_stream_throws_exception(): void
    {
        $stream = Stream::empty()->limit(5)->onlyIntegers();
        self::assertTrue($stream->isEmpty()->get());
        
        $this->expectExceptionObject(StreamExceptionFactory::cannotReuseUtilizedStream());
        
        $stream->wrap(['a']);
    }
    
    public function test_wrap_producer_by_stream_with_fork_and_dispatch(): void
    {
        $sumInts = Reducers::sum();
        $concatStrings = Reducers::concat(',');
        
        $prototype = Stream::empty()
            ->dispatch('is_string', [
                0 => $sumInts,
                1 => $concatStrings
            ])
            ->onlyIntegers()
            ->fork(Discriminators::evenOdd(), Collectors::values());
        
        $actual = $prototype->wrap(['a', 3, 'b', 2, 'c', 5, 'd', 4, 'e', 1, 'f', 6, 'g', 7, 'h', 8])->toArrayAssoc();
        
        self::assertSame(36, $sumInts->result());
        self::assertSame('a,b,c,d,e,f,g,h', $concatStrings->result());
        
        self::assertSame([
            'odd' => [3, 5, 1, 7],
            'even' => [2, 4, 6, 8],
        ], $actual);
        
        $prototype->wrap(Stream::of('the brown quick fox')->tokenize())->run();
        
        self::assertSame(36, $sumInts->result());
        self::assertSame('a,b,c,d,e,f,g,h,the,brown,quick,fox', $concatStrings->result());
    }
    
    public function test_wrap_on_stream_with_last_operation_does_not_affect_it(): void
    {
        $stream = Stream::empty()->onlyIntegers();
        $sum = $stream->reduce(Reducers::sum());
        
        $stream->wrap(['a', 1, 'b', 2, 'c', 3])->run();
        
        self::assertFalse($sum->found());
        self::assertSame(0, $sum->count());
        self::assertNull($sum->get());
    }
    
    public function test_LastOperation_allows_to_wrap_producer_and_all_results_are_independent(): void
    {
        $stream = Stream::empty()->onlyIntegers()->reduce(Reducers::sum());
        
        $sum1 = $stream->wrap(['a', 1, 'b', 2, 'c', 3]);
        self::assertTrue($sum1->found());
        self::assertSame(1, $sum1->count());
        self::assertSame(6, $sum1->get());
        
        $sum2 = $stream->wrap(['a', 'b', 'c']);
        self::assertFalse($sum2->found());
        self::assertSame(0, $sum2->count());
        self::assertNull($sum2->get());
        
        $sum3 = $stream->wrap([5, 5]);
        self::assertTrue($sum3->found());
        self::assertSame(1, $sum3->count());
        self::assertSame(10, $sum3->get());
        
        //previous results remain unchanged
        self::assertNull($sum2->get());
        self::assertSame(6, $sum1->get());
    }
    
    public function test_reduce_numbers_to_basic_stats(): void
    {
        $degrees = [16.6, 18.2, 15.9, 22.3, 17.8, 14.0, 19.3];
        
        self::assertSame([
            'count' => \count($degrees),
            'min' => \min($degrees),
            'max' => \max($degrees),
            'sum' => \array_sum($degrees),
            'avg' => \array_sum($degrees) / \count($degrees),
        ], Stream::from($degrees)->reduce(Reducers::basicStats())->get());
    }
    
    public function test_use_BasicStats_Categorize_Sort_to_reduce_data(): void
    {
        $data = [
            'C;14.2', 'B;13.8', 'A;15.6', 'D;12.9', 'B;15.5', 'A;18.2', 'D;19.3', 'C;9.6', 'A;13.2', 'D;14.1', 'E;15.5',
        ];
        
        $actual = Stream::from($data)
            ->split(';')
            ->unpackTuple()
            ->castToFloat()
            ->categorizeByKey()
            ->map(Reducers::basicStats(2))
            ->extract(['min', 'avg', 'max'])
            ->concat(';')
            ->sort(By::key());
            
        self::assertSame([
            'A' => '13.2;15.67;18.2',
            'B' => '13.8;14.65;15.5',
            'C' => '9.6;11.9;14.2',
            'D' => '12.9;15.43;19.3',
            'E' => '15.5;15.5;15.5',
        ], $actual->toArrayAssoc());
    }
    
    public function test_use_BasicStats_Fork_Segregate_to_reduce_data(): void
    {
        $data = [
            'C;14.2', 'B;13.8', 'A;15.6', 'D;12.9', 'B;15.5', 'A;18.2', 'D;19.3', 'C;9.6', 'A;13.2', 'D;14.1', 'E;15.5',
        ];
        
        $actual = Stream::from($data)
            ->split(';')
            ->unpackTuple()
            ->fork(Discriminators::byKey(), Stream::empty()->castToFloat()->reduce(Reducers::basicStats(2)))
            ->extract(['min', 'avg', 'max'])
            ->concat(';')
            ->segregate(null, false, By::key())
            ->flat(1);
            
        self::assertSame([
            'A' => '13.2;15.67;18.2',
            'B' => '13.8;14.65;15.5',
            'C' => '9.6;11.9;14.2',
            'D' => '12.9;15.43;19.3',
            'E' => '15.5;15.5;15.5',
        ], $actual->toArrayAssoc());
    }
    
    public function test_use_BasicStats_Fork_Sort_to_reduce_data(): void
    {
        $data = [
            'C;14.2', 'B;13.8', 'A;15.6', 'D;12.9', 'B;15.5', 'A;18.2', 'D;19.3', 'C;9.6', 'A;13.2', 'D;14.1', 'E;15.5',
        ];
        
        $actual = Stream::from($data)
            ->split(';')
            ->unpackTuple()
            ->fork(Discriminators::byKey(), Stream::empty()->castToFloat()->reduce(Reducers::basicStats(2)))
            ->extract(['min', 'avg', 'max'])
            ->concat(';')
            ->sort(By::key());
            
        self::assertSame([
            'A' => '13.2;15.67;18.2',
            'B' => '13.8;14.65;15.5',
            'C' => '9.6;11.9;14.2',
            'D' => '12.9;15.43;19.3',
            'E' => '15.5;15.5;15.5',
        ], $actual->toArrayAssoc());
    }
    
    /**
     * @dataProvider getDataForTestUntilWithFilterInVariousWays
     */
    #[DataProvider('getDataForTestUntilWithFilterInVariousWays')]
    public function test_until_with_filter_in_various_ways(Stream $stream): void
    {
        self::assertSame(['a' => 'b', 'c' => 1, 2 => 'd'], $stream->toArrayAssoc());
    }

    public static function getDataForTestUntilWithFilterInVariousWays(): iterable
    {
        $data = Producers::getAdapter(['a' => 'b', 'c' => 1, 2 => 'd', 3 => 4, 5 => 'e', 'f' => 6]);

        $bothNotStrings = static fn($v, $k): bool => !\is_string($v) && !\is_string($k);
        $anyIsString = static fn($v, $k): bool => \is_string($v) || \is_string($k);

        yield [$data->stream()->until($bothNotStrings)];
        yield [$data->stream()->while(Filters::NOT($bothNotStrings))];

        yield [$data->stream()->while($anyIsString)];
        yield [$data->stream()->until(Filters::NOT($anyIsString))];

        yield [$data->stream()->while(Filters::isString(Check::ANY))];

        yield [$data->stream()->until(Filters::isInt(Check::BOTH))];
    }
    
    public function test_while_not_any_is_int(): void
    {
        $data = Producers::getAdapter(['a' => 'b', 'c' => 1, 2 => 'd', 3 => 4, 5 => 'e', 'f' => 6]);
        $result = $data->stream()->while(Filters::NOT(Filters::isInt(Check::ANY)))->toArrayAssoc();

        self::assertSame(['a' => 'b'], $result);
    }
    
    public function test_until_not_both_are_strings(): void
    {
        $data = Producers::getAdapter(['a' => 'b', 'c' => 1, 2 => 'd', 3 => 4, 5 => 'e', 'f' => 6]);
        
        $result = $data->stream()->until(Filters::NOT(Filters::isString(Check::BOTH)))->toArrayAssoc();
        self::assertSame(['a' => 'b'], $result);
        
        $result = $data->stream()->until(Filters::isInt(Check::ANY))->toArrayAssoc();
        self::assertSame(['a' => 'b'], $result);
    }
    
    public function test_until_with_filter(): void
    {
        $producer = Producers::getAdapter(['c' => 1, 2 => 'd', 'a' => 'b', 3 => 4, 5 => 'e', 'f' => 6]);
        $isString = Filters::isString();
        
        //stream until value is a string
        self::assertSame([
            'c' => 1,
        ], $producer->stream()->until($isString)->toArrayAssoc());
        
        //stream until value is not a string
        self::assertSame([], $producer->stream()->until($isString->negate())->toArrayAssoc());
        
        //stream until key is a string
        self::assertSame([], $producer->stream()->until($isString->checkKey())->toArrayAssoc());
        
        //stream until key is not a string
        self::assertSame([
            'c' => 1,
        ], $producer->stream()->until($isString->checkKey()->negate())->toArrayAssoc());
        
        //stream until both value and key are strings
        self::assertSame([
            'c' => 1, 2 => 'd',
        ], $producer->stream()->until($isString->checkBoth())->toArrayAssoc());
        
        //stream until not(value is string and key is string)
        // = until (value is not string or key is not string)
        // = while (value is string and key is string)
        self::assertSame([], $producer->stream()->until($isString->checkBoth()->negate())->toArrayAssoc());
        
        //stream until any of value or key is a string
        self::assertSame([], $producer->stream()->until($isString->checkAny())->toArrayAssoc());
        
        //stream until not(value is string or key is string)
        // = until (value is not string and key is not string)
        // = while (value is string or key is string)
        self::assertSame([
            'c' => 1, 2 => 'd', 'a' => 'b'
        ], $producer->stream()->until($isString->checkAny()->negate())->toArrayAssoc());
    }

    public function test_only_mode_value(): void
    {
        self::assertSame([1 => 5, 2 => 3, 4 => 1], Stream::from([2, 5, 3, 4, 1])->only([1, 3, 5, 7])->toArrayAssoc());
    }
    
    public function test_only_mode_key(): void
    {
        self::assertSame(
            [5 => 1, 3 => 2, 1 => 4],
            Stream::from([2, 5, 3, 4, 1])->flip()->only([1, 3, 5, 7], Check::KEY)->toArrayAssoc()
        );
    }
    
    public function test_only_mode_both(): void
    {
        self::assertSame(
            [1 => 5, 7 => 3],
            Stream::from([2, 5, 3, 4, 1, 8, 5, 3, 1])->only([1, 3, 5, 7], Check::BOTH)->toArrayAssoc()
        );
    }
    
    public function test_only_mode_any(): void
    {
        self::assertSame(
            [1 => 5, 3, 4, 1, 8, 5, 3, 1],
            Stream::from([2, 5, 3, 4, 1, 8, 5, 3, 1, 6])->only([1, 3, 5, 7], Check::ANY)->toArrayAssoc()
        );
    }
    
    public function test_without_mode_value(): void
    {
        self::assertSame([2, 3 => 4], Stream::from([2, 5, 3, 4, 1])->without([1, 3, 5, 7])->toArrayAssoc());
    }
    
    public function test_without_mode_key(): void
    {
        self::assertSame(
            [2 => 0, 4 => 3],
            Stream::from([2, 5, 3, 4, 1])->flip()->without([1, 3, 5, 7], Check::KEY)->toArrayAssoc()
        );
    }
    
    public function test_without_mode_both(): void
    {
        self::assertSame(
            [2, 2 => 3, 4, 1, 8, 5, 6, 0],
            Stream::from([2, 5, 3, 4, 1, 8, 5, 6, 0])->without([1, 3, 5, 7], Check::BOTH)->toArrayAssoc()
        );
    }
    
    public function test_without_mode_any(): void
    {
        self::assertSame(
            [2, 8 => 0],
            Stream::from([2, 5, 3, 4, 1, 8, 5, 6, 0])->without([1, 3, 5, 7], Check::ANY)->toArrayAssoc()
        );
    }
    
    public function test_filter_many_with_various_filters(): void
    {
        $actual = Stream::from([-2 => 5, 2 => 7, 'a' => 3, 7 => 'b', 3 => -1, 0 => 0, 1 => 3, 9 => 5, 5 => 7])
            ->filter(Filters::NOT(Filters::isString(Check::KEY))->and(Filters::isInt(Check::VALUE)))
            ->filter(Filters::greaterThan(0))
            ->omit(Filters::lessThan(0), Check::KEY)
            ->filter(Filters::number()->isOdd(), Check::BOTH)
            ->without([1, 7], Check::ANY);
        
        self::assertSame([9 => 5], $actual->toArrayAssoc());
    }
    
    public function test_iterate_result_of_groupBy(): void
    {
        $stream = Stream::from([['a', 1], ['b', 2], ['a', 3], ['c', 4], ['b', 5], ['c', 6], ['a', 7]])
            ->unpackTuple()
            ->group();

        $result = [];
        foreach ($stream as $classifier => $values) {
            foreach ($values as $key => $value) {
                $result[$classifier][$key] = $value;
            }
        }
        
        self::assertSame([
            'a' => [1, 3, 7],
            'b' => [2, 5],
            'c' => [4, 6],
        ], $result);
    }
    
    public function test_filter_unique_pairs_of_key_value(): void
    {
        $result = Stream::from([[3, 'a'], [3, 'b'], [4, 'a'], [4, 'b'], [3, 'b'], ['b', 3], [4, 'a'], ['a', 3]])
            ->unpackTuple()
            ->unique(Compare::pairs())
            ->makeTuple()
            ->toArray();
        
        self::assertSame([[3, 'a'], [3, 'b'], [4, 'a'], [4, 'b'], ['b', 3], ['a', 3]], $result);
    }
    
    public function test_filter_unique_pairs_of_key_value_using_ComparatorReady_adapters(): void
    {
        $result = Stream::from(['a', 'a', 'A', 'A', 'b', 'B', 'B', 'b'])
            ->unique(Compare::pairs(Filters::getAdapter('ctype_upper'), Discriminators::evenOdd()))
            ->toArrayAssoc();
        
        self::assertSame(['a', 'a', 'A', 'A'], $result);
    }
    
    /**
     * @dataProvider getDataForTestOmitRepsUsingComparatorReadyAdapterForPairsComparison
     */
    #[DataProvider('getDataForTestOmitRepsUsingComparatorReadyAdapterForPairsComparison')]
    public function test_omitReps_using_ComparatorReady_adapter_for_pairs_comparison(Comparison $comparison): void
    {
        $data = [
            0 => 6, 1 => 3, 3 => 5, 4 => 2, 6 => 4, 7 => 3, 9 => 5, 10 => 1, 11 => 4, 13 => 2, 15 => 3, 16 => 4,
            18 => 2, 19 => 5, 21 => 1,
        ];
        
        $actual = Stream::from($data)
            ->omitReps($comparison)
            ->toArrayAssoc();
        
        self::assertSame([0 => 6, 1 => 3, 4 => 2, 7 => 3, 10 => 1, 11 => 4, 15 => 3, 16 => 4, 19 => 5], $actual);
    }
    
    public static function getDataForTestOmitRepsUsingComparatorReadyAdapterForPairsComparison(): array
    {
        $discriminator = Discriminators::evenOdd();
        $filter = Filters::number()->isOdd();
        
        return [
            'DiscriminatorAdapter' => [Compare::pairs($discriminator, $discriminator)],
            'FilterAdapter' => [Compare::pairs($filter, $filter)],
        ];
    }
    
    public function test_segregate_with_limited_max_number_of_elements_in_each_bucket(): void
    {
        $data = [1, 5, 2, 3, 2, 7, 4, 1, 6, 3, 5, 2, 4, 1, 7, 2, 3, 2, 6, 3, 2, 5, 8, 4];
        
        self::assertSame([
            [0 => 1, 7 => 1, 13 => 1],
            [2 => 2, 4 => 2, 11 => 2, 15 => 2],
            [3 => 3, 9 => 3, 16 => 3, 19 => 3],
        ], Stream::from($data)->segregate(3, false, null, 4)->toArray());
    }
    
    public function test_stream_file_Producers_resource_file(): void
    {
        $this->examineTextFileReader(Producers::resource(__DIR__.'/CollectionTest.php'));
    }
    
    public function test_stream_file_Producers_getAdapter_file(): void
    {
        $this->examineTextFileReader(Producers::getAdapter(__DIR__.'/CollectionTest.php'));
    }
    
    public function test_stream_file_Producers_resource_with_long_readBytes(): void
    {
        $this->examineTextFileReader(Producers::resource(\fopen(__DIR__.'/CollectionTest.php', 'rb'), true, 4096));
    }
    
    public function test_stream_file_more_than_once(): void
    {
        $producer = Producers::getAdapter(__DIR__.'/CollectionTest.php');
        
        $this->examineTextFileReader($producer);
        $this->examineTextFileReader($producer);
    }
    
    private function examineTextFileReader(Producer $producer): void
    {
        Stream::from($producer)
            ->countIn($countAllLines)
            ->trim()
            ->notEmpty()
            ->countIn($countNonEmptyLines)
            ->run();
        
        self::assertSame(102, $countAllLines);
        self::assertSame(83, $countNonEmptyLines);
    }
    
    public function test_stream_few_times_over_datetime_sequence(): void
    {
        $dates = Producers::dateTimeSeq('2001-01-01')
            ->stream()
            ->limit(3)
            ->map(static fn(\DateTimeImmutable $dt): string => $dt->format('Y-m-d H:i:s'))
            ->toArray();
        
        self::assertSame([
            '2001-01-01 00:00:00',
            '2001-01-02 00:00:00',
            '2001-01-03 00:00:00',
        ], $dates);
    }
    
    public function test_count_number_of_working_days_in_date_range_using_dateTimeSeq_producer(): void
    {
        $workingDays = Producers::dateTimeSeq('2024-12-01', '1 day', '2024-12-31')
            ->stream()
            ->omit(Filters::time()->isDay(Day::SAT, Day::SUN))
            ->omit(Filters::time()->inSet(['2024-12-25', '2024-12-26']))
            ->count();
        
        self::assertSame(20, $workingDays->get());
    }
    
    public function test_count_number_of_working_days_in_date_range_using_mutable_variable_and_call(): void
    {
        $day = new \DateTime('2024-12-01');
        
        $workingDays = Stream::empty()
            ->filter(Filters::time()->isNotDay(Day::SAT, Day::SUN))
            ->filter(Filters::time()->notInSet(['2024-12-25', '2024-12-26']))
            ->count();
        
        Producers::readFrom($day)
            ->stream()
            ->feed($workingDays)
            ->call(static function () use ($day) {
                $day->modify('+1 day');
            })
            ->until(Filters::time()->is('2025-01-01'));
        
        self::assertSame(20, $workingDays->get());
    }
    
    public function test_count_number_of_working_days_in_date_range_using_loop_stream_and_countIn(): void
    {
        Stream::of(new \DateTimeImmutable('2024-12-01'))
            ->feed(Stream::empty()
                ->omit(Filters::time()->isDay(Day::SAT, Day::SUN))
                ->omit(Filters::time()->inSet(['2024-12-25', '2024-12-26']))
                ->countIn($workingDays)
            )
            ->map(static fn(\DateTimeImmutable $dt): \DateTimeImmutable => $dt->modify('+1 day'))
            ->while(Filters::time()->before('2025-01-01'))
            ->loop(true);
            
        self::assertSame(20, $workingDays);
    }
    
    public function test_count_number_of_working_days_in_date_range_using_queue_producer_and_dedicated_filter(): void
    {
        $isDayOff = Filters::time()->isDay(Day::SAT, Day::SUN)
            ->or(Filters::time()->inSet(['2024-12-25', '2024-12-26']));
        
        Stream::from($queue = Producers::queue([new \DateTimeImmutable('2024-12-01')]))
            ->callWhen(Filters::NOT($isDayOff), $workingDays = Consumers::counter())
            ->map(static fn(\DateTimeImmutable $dt): \DateTimeImmutable => $dt->modify('+1 day'))
            ->callWhen(Filters::time()->until('2024-12-31'), $queue);
        
        self::assertSame(20, $workingDays->count());
    }
    
    public function test_streaming_of_invalid_datetime_values_throws_exception(): void
    {
        $this->expectExceptionObject(UnsupportedValueException::cannotCastNonTimeObjectToString('tomorrow'));
        
        Stream::from([new \DateTime('now'), 'tomorrow', new \DateTimeImmutable('yesterday')])
            ->map(Mappers::formatTime())
            ->run();
    }
    
    public function test_Day_constants_are_valid(): void
    {
        $result = Producers::dateTimeSeq('2024-04-15')
            ->stream()
            ->limit(7)
            ->filter(Filters::time()->isDay(Day::MON, Day::TUE, Day::WED, Day::THU, Day::FRI, Day::SAT, Day::SUN))
            ->classify(Discriminators::dayOfWeek())
            ->map(Mappers::formatTime('d'))
            ->castToInt()
            ->toArrayAssoc();
        
        self::assertSame([
            Day::MON => 15,
            Day::TUE => 16,
            Day::WED => 17,
            Day::THU => 18,
            Day::FRI => 19,
            Day::SAT => 20,
            Day::SUN => 21,
        ], $result);
    }
    
    public function test_trigger_stream_by_calling_last_operation_in_deeply_nested_stream(): void
    {
        $stream1 = Stream::from(['a', 1, 'b', 2])->onlyIntegers();
        $stream2 = Stream::empty()->join([3, 4, 5])->map(Mappers::increment(2));
        $stream3 = Stream::empty()->greaterThan(3)->lessThan(7);
        $stream4 = Stream::empty()->collect();
        
        $stream1->feed($stream2);
        $stream2->feed($stream3);
        $stream3->feed($stream4);
        
        self::assertSame([4, 5, 6], $stream4->toArray());
    }
    
    public function test_iterate_over_multiple_streams_when_triggerring_stream_has_many_parents(): void
    {
        $stream1 = Stream::from(['a', 1, 'b', 2])->onlyIntegers();
        $stream2 = Stream::empty()->join([3, 4, 5])->map(Mappers::increment(2));
        $stream3 = Stream::from([7, 6, 5, 4, 3])->greaterThan(3)->lessThan(7);
        $stream4 = Stream::empty()->collect(true);
        
        $stream1->feed($stream2);
        $stream2->feed($stream4);
        
        $stream3->feed($stream4);
        
        self::assertSame([3, 4, 5, 6, 7, 6, 5, 4], $stream4->toArray());
    }
    
    public function test_use_counter_by_many_streams_in_the_same_time(): void
    {
        $counter = Consumers::counter();
        
        $stream1 = Stream::from(['a', 1, 'b', 2])->onlyIntegers()->call($counter);
        $stream2 = Stream::empty()->join([3, 4, 5])->map(Mappers::increment(2))->call($counter);
        $stream3 = Stream::empty()->join([1, 5, 9])->greaterThan(3)->lessThan(7)->call($counter);
        $stream4 = Stream::empty()->call($counter)->collect();
        
        //s1->s2->s3->s4; s1->s4
        $stream1->feed($stream2, $stream4);
        $stream2->feed($stream3);
        $stream3->feed($stream4);
    
        //s1:[1] c:1
        //s1 -> [1] -> s2, s2 -> [3], s2:[3] c:2
        //s2 -> [3] -> s3
        //s1 -> [1] -> s4, s4:[1] c:3
        
        //s1:[2] c:4
        //s1 -> [2] -> s2, s2 -> [4], s2:[4] c:5
        //s2 -> [4] -> s3, s3:[4] c:6
        //s3 -> [4] -> s4, s4:[4] c:7
        //s1 -> [2] -> s4, s4:[2] c:8
        
        //s2:[5] c:9
        //s2 -> [5] -> s3, s3:[5] c:10
        //s3 -> [5] -> s4, s4:[5] c:11
        
        //s2:[6] c:12
        //s2 -> [6] -> s3, s3:[6] c:13
        //s3 -> [6] -> s4, s4:[6] c:14
        
        //s2:[7] c:15
        //s2 -> [7] -> s3
        
        //s3:[5] c:16
        //s3 -> [5] -> s4, s4:[5] c:17
        
        self::assertSame(17, $counter->count());
    }
    
    public function test_how_Counter_methods_get_and_count_work(): void
    {
        //given
        $counter = Consumers::counter();
        Stream::from(['a', 1, 'b', 2])->onlyIntegers()->call($counter);
        
        //method get() returns current value hold by counter
        self::assertSame(0, $counter->get());
        
        //method count() triggers stream iterating
        self::assertSame(2, $counter->count());
        
        //and then, get() returns counted value
        self::assertSame(2, $counter->get());
        
        //both method can be called again but it does nothing
        self::assertSame(2, $counter->count());
        self::assertSame(2, $counter->get());
    }
    
    public function test_Counter_method_count_can_be_called_after_iterating_the_stream(): void
    {
        //given
        $counter = Consumers::counter();
        $stream = Stream::from(['a', 1, 'b', 2])->onlyIntegers()->call($counter);
        
        //when
        $stream->run();
        
        //then
        self::assertSame(2, $counter->get());
        self::assertSame(2, $counter->count());
    }
    
    public function test_trigger_processing_by_the_last_element_from_feedMany_operation(): void
    {
        $stream1 = Stream::from(['a', 'b']);
        $stream2 = Stream::from(['c', 'd']);
        $stream3 = Stream::from(['e', 'f'])->collect(true);
        $stream4 = Stream::from(['g', 'h'])->collect(true);
        
        $stream1->feed($stream2);
        $stream2->feed($stream3, $stream4);
        
        //s1->s2->[s3,s4]
        
        self::assertSame('abcdgh', $stream4->toString(''));
        self::assertSame('abcdef', $stream3->toString(''));
    }
    
    public function test_trigger_processing_by_the_first_stream_with_feedMany(): void
    {
        //given
        $stream1 = Stream::from(['a', 'b']);
        $stream2 = Stream::from(['c', 'd']);
        $stream3 = Stream::from(['e', 'f'])->collect(true);
        $stream4 = Stream::from(['g', 'h'])->collect(true);
        
        $stream1->feed($stream2);
        $stream2->feed($stream3, $stream4);
        
        //when
        $stream1->run();
        
        //then
        self::assertSame('abcdgh', $stream4->toString(''));
        self::assertSame('abcdef', $stream3->toString(''));
    }
    
    public function test_onFinish_and_onSuccess_handlers_are_executed_only_once_1(): void
    {
        //given
        $cntSuccess = 0;
        $cntFinish = 0;
        
        $onSuccess = static function () use (&$cntSuccess) {
            ++$cntSuccess;
        };
        
        $onFinish = static function () use (&$cntFinish) {
            ++$cntFinish;
        };
        
        $stream1 = Stream::from(['a', 'b'])->onSuccess($onSuccess)->onFinish($onFinish);
        $stream2 = Stream::from(['c', 'd'])->onSuccess($onSuccess)->onFinish($onFinish);
        $stream3 = Stream::from(['e', 'f'])->onSuccess($onSuccess)->onFinish($onFinish)->collect(true);
        $stream4 = Stream::from(['g', 'h'])->onSuccess($onSuccess)->onFinish($onFinish)->collect(true);
        
        $stream1->feed($stream2);
        $stream2->feed($stream3, $stream4);
        
        //when
        $stream1->run();
        
        //then
        self::assertSame(4, $cntSuccess);
        self::assertSame(4, $cntFinish);
    }
    
    public function test_onFinish_and_onSuccess_handlers_are_executed_only_once_2(): void
    {
        //given
        $cntSuccess = 0;
        $cntFinish = 0;
        
        $onSuccess = static function () use (&$cntSuccess) {
            ++$cntSuccess;
        };
        
        $onFinish = static function () use (&$cntFinish) {
            ++$cntFinish;
        };
        
        $stream1 = Stream::from(['a', 'b'])->onSuccess($onSuccess)->onFinish($onFinish);
        $stream2 = Stream::from(['c', 'd'])->onSuccess($onSuccess)->onFinish($onFinish);
        $stream3 = Stream::from(['e', 'f'])->onSuccess($onSuccess)->onFinish($onFinish)->collect(true);
        $stream4 = Stream::from(['g', 'h'])->onSuccess($onSuccess)->onFinish($onFinish)->collect(true);
        
        $stream1->feed($stream2);
        $stream2->feed($stream3, $stream4);
        
        //when
        $stream4->toString();
        $stream3->toString();
        
        //then
        self::assertSame(4, $cntSuccess);
        self::assertSame(4, $cntFinish);
    }
    
    public function test_LastOperation_in_fork_doesnt_trigger_streaming_of_parent_stream(): void
    {
        $collector = Stream::empty()->castToString()->reduce(Reducers::concat());
        
        Stream::from(['a', 1, 'b', 2])->fork('is_string', $collector);
        
        self::assertFalse($collector->found());
        self::assertNull($collector->get());
    }
    
    public function test_when_triggered_stream_has_more_than_one_independent_parents(): void
    {
        //given
        $stream1 = Stream::from(['a', 'b']);
        $stream2 = Stream::from(['c', 'd']);
        $stream3 = Stream::from(['e', 'f'])->collect(true);
        $stream4 = Stream::from(['g', 'h'])->collect(true);
        
        //s1->s4, s2->[s3,s4]
        $stream1->feed($stream4);
        $stream2->feed($stream3, $stream4);
        
        //when
        $stream1->run();
        
        //then
        self::assertSame('abghcd', $stream4->toString(''));
        self::assertSame('cdef', $stream3->toString(''));
    }
    
    public function test_use_Entry_value_holder(): void
    {
        $quantity = Memo::value();
        
        $result = Stream::from([5, 'Foo', 3, 'Bar', 'Zoo', 4, 'Joe'])
            ->remember($quantity)
            ->readWhile('is_string')
            ->mapKV(static fn(string $position): array => [$position => $quantity->read()])
            ->toArrayAssoc();
            
        self::assertSame([
            'Foo' => 5,
            'Bar' => 3,
            'Zoo' => 3,
            'Joe' => 4,
        ], $result);
    }
    
    public function test_use_Entry_key_holder_1(): void
    {
        $quantity = Memo::key();
        
        $result = Stream::from([5 => ['Foo'], 3 => ['Bar', 'Zoo'], 4 => ['Joe']])
            ->remember($quantity)
            ->flat()
            ->mapKV(static fn(string $position): array => [$position => $quantity->read()])
            ->toArrayAssoc();
            
        self::assertSame([
            'Foo' => 5,
            'Bar' => 3,
            'Zoo' => 3,
            'Joe' => 4,
        ], $result);
    }
    
    public function test_use_Entry_key_holder_2(): void
    {
        $quantity = Memo::key();
        
        $result = Stream::from([5 => ['Foo'], 3 => ['Bar', 'Zoo'], 4 => ['Joe']])
            ->remember($quantity)
            ->flat()
            ->mapKey($quantity)
            ->flip()
            ->toArrayAssoc();
            
        self::assertSame([
            'Foo' => 5,
            'Bar' => 3,
            'Zoo' => 3,
            'Joe' => 4,
        ], $result);
    }
    
    public function test_use_Entry_key_holder_3(): void
    {
        $quantity = Memo::key();
        
        $result = Stream::from([5 => ['Foo'], 3 => ['Bar', 'Zoo'], 4 => ['Joe']])
            ->remember($quantity)
            ->flat()
            ->flip()
            ->map($quantity)
            ->toArrayAssoc();
            
        self::assertSame([
            'Foo' => 5,
            'Bar' => 3,
            'Zoo' => 3,
            'Joe' => 4,
        ], $result);
    }
    
    public function test_use_Entry_full_holder(): void
    {
        //given
        $data = ['Foo' => 5, 'Bar' => 3, 'Zoo' => 3, 'Joe' => 4];
        $expected = [5 => ['Foo'], 3 => ['Bar', 'Zoo'], 4 => ['Joe']];
        
        //when
        $item = Memo::full();
        
        $result = Stream::from($data)
            ->remember($item)
            ->map($item->key())
            ->categorize($item->value(), true)
            ->toArrayAssoc();
        
        //then
        self::assertSame($expected, $result);
        
        //but much simpler way to do the same is:
        
        $result = Stream::from($data)
            ->flip()
            ->categorizeByKey()
            ->toArrayAssoc();
        
        self::assertSame($expected, $result);
    }
    
    public function test_read_from_Entry_full_holder_as_tuple(): void
    {
        //given
        $data = ['Foo' => 5, 'Bar' => 3, 'Zoo' => 3, 'Joe' => 4];
        $expected = '[["Foo",5],["Bar",3],["Zoo",3],["Joe",4]]';
        
        //when
        $item = Memo::full();
        $result = Stream::from($data)->remember($item)->map($item->tuple())->toJson();
        
        //then
        self::assertSame($expected, $result);
        
        //it works the same like:
        self::assertSame($expected, Stream::from($data)->makeTuple()->toJson());
    }
}