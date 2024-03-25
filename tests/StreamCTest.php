<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Comparator\Comparators;
use FiiSoft\Jackdaw\Comparator\Comparison\Compare;
use FiiSoft\Jackdaw\Comparator\Sorting\By;
use FiiSoft\Jackdaw\Consumer\Consumers;
use FiiSoft\Jackdaw\Discriminator\Discriminators;
use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Exception\StreamExceptionFactory;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Filter\IdleFilter;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Mapper\Mappers;
use FiiSoft\Jackdaw\Operation\Exception\OperationExceptionFactory;
use FiiSoft\Jackdaw\Producer\Producers;
use FiiSoft\Jackdaw\Reducer\Reducers;
use FiiSoft\Jackdaw\Registry\Registry;
use FiiSoft\Jackdaw\Stream;
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
        $result = Stream::from(['a', 'b', 'c', 'd', 'e'])
            ->map(new \ArrayIterator([5, 4, 3, 2]))
            ->toArray();
        
        self::assertSame([5, 4, 3, 2, 'e'], $result);
    }
    
    public function test_use_Result_as_source_of_values_for_mapper(): void
    {
        $numbers = Stream::from([5, 4, 3, 2])->collect();
        
        $result = Stream::from(['a', 'b', 'c', 'd', 'e'])
            ->moveTo('char')
            ->append('number', $numbers)
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
        $result = Stream::from(self::ROWSET)
            ->mapField('age', Producers::collatz(8))
            ->toArray();
        
        $expected = [
            ['id' => 2, 'name' => 'Sue', 'age' => 8],
            ['id' => 9, 'name' => 'Chris', 'age' => 4],
            ['id' => 6, 'name' => 'Joanna', 'age' => 2],
            ['id' => 5, 'name' => 'Chris', 'age' => 1],
            ['id' => 7, 'name' => 'Sue', 'age' => 18],
        ];
        
        self::assertSame($expected, $result);
    }
    
    public function test_use_Generator_as_source_of_values_for_mapper(): void
    {
        $words = static function () {
            yield from ['this', 'is', 'it'];
        };
        
        $result = Stream::from(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4])
            ->mapKey($words())
            ->toArrayAssoc();
        
        self::assertSame(['this' => 1, 'is' => 2, 'it' => 3, 'd' => 4], $result);
    }
    
    public function test_use_the_same_Generator_as_Mapper_for_values_and_keys(): void
    {
        $words = static function () {
            yield from ['this', 'is', 'it'];
        };
        
        $result = Stream::from(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4])
            ->mapKey($words())
            ->map($words())
            ->toArrayAssoc();
        
        self::assertSame(['this' => 'this', 'is' => 'is', 'it' => 'it', 'd' => 4], $result);
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
        
        $result = Stream::from(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4])
            ->mapKey($numbers)
            ->map($numbers)
            ->toArrayAssoc();
        
        self::assertSame([1 => 1, 2 => 2, 3 => 3, 'd' => 4], $result);
    }
    
    /**
     * @dataProvider getDataForTestUniqueThoroughly
     */
    public function test_Unique_compare_values_by_default_comparator_in_various_ways(array $data, array $expected): void
    {
        self::assertSame(
            $expected,
            Stream::from($data)->unique()->toArray()
        );

        self::assertSame(
            $expected,
            Stream::from($data)->unique(Compare::values())->toArray()
        );
        
        self::assertSame(
            $expected,
            Stream::from($data)->unique(Comparators::default())->toArray()
        );
        
        self::assertSame(
            $expected,
            Stream::from($data)->unique(Compare::values(Comparators::default()))->toArray()
        );
        
        self::assertSame(
            $expected,
            Stream::from($data)->unique(By::value())->toArray()
        );
        
        self::assertSame(
            $expected,
            Stream::from($data)->unique(By::value(Comparators::default()))->toArray()
        );
    }
    
    /**
     * @dataProvider getDataForTestUniqueThoroughly
     */
    public function test_Unique_compare_values_by_custom_comparator_in_various_ways(array $data, array $expected): void
    {
        $comparator = static fn($a, $b): int => \gettype($a) <=> \gettype($b) ?: $a <=> $b;
        
        self::assertSame(
            $expected,
            Stream::from($data)->unique($comparator)->toArray()
        );
        
        self::assertSame(
            $expected,
            Stream::from($data)->unique(Compare::values($comparator))->toArray()
        );
        
        self::assertSame(
            $expected,
            Stream::from($data)->unique(Comparators::getAdapter($comparator))->toArray()
        );
        
        self::assertSame(
            $expected,
            Stream::from($data)->unique(Compare::values(Comparators::getAdapter($comparator)))->toArray()
        );
        
        self::assertSame(
            $expected,
            Stream::from($data)->unique(By::value($comparator))->toArray()
        );
        
        self::assertSame(
            $expected,
            Stream::from($data)->unique(By::value(Comparators::getAdapter($comparator)))->toArray()
        );
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
        //given
        $keys =   [0,  'b', 2, 1,   'a', 1,  'b',   2,    'c'];
        $values = ['a', 3,  2, 'a', 'b', 'b', true, true, 'a'];
        
        $producer = Producers::combinedFrom($keys, $values);
        
        //when
        $result = $producer->stream()->sort(By::bothAsc())->makeTuple()->toArray();
        
        //then
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
        ], $result);
    }
    
    public function test_Sort_by_both_desc(): void
    {
        //given
        $keys =   [0,  'b', 2, 1,   'a', 1,  'b',   2,    'c'];
        $values = ['a', 3,  2, 'a', 'b', 'b', true, true, 'a'];
        
        $producer = Producers::combinedFrom($keys, $values);
        
        //when
        $result = $producer->stream()->sort(By::bothDesc())->makeTuple()->toArray();
        
        //then
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
        ], $result);
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
            ->fork($discriminator, Stream::empty()->reduce(Reducers::sum()))
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
            ->remember($threshold)
            ->toArray();
        
        self::assertSame([6, 5, 5, 2, 2, 1, 1], $result);
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
        $result = Stream::from(['a', 'b', 'c', 'd', 'e', 'f'])->window(3, 2)->toArrayAssoc();

        $expected = [
            [0 => 'a', 'b', 'c'],
            [2 => 'c', 'd', 'e'],
            [4 => 'e', 'f'],
        ];

        self::assertSame($expected, $result);
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
        
        $countIterations = 0;
        
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
        
        $stream = Stream::from([6, 2, 4, 1])->storeIn($buffer)->find(2, Check::KEY);
        
        self::assertTrue($stream->found());
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
        
        $producer = Producers::combinedFrom($keys, $values);
        
        //when
        $keyIsIntAndValIsStr = Filters::isInt()->checkKey()->and(Filters::isString()->checkValue());
        $keyIsStrAndValIsInt = Filters::isString(Check::KEY)->and(Filters::isInt(Check::VALUE));
        $keyAndValAreEqualInts = Filters::isInt(Check::BOTH)->and(fn(int $v, int $k): bool => $v === $k);
        
        $actual = Stream::from($producer)
            ->filter($keyIsIntAndValIsStr->or($keyIsStrAndValIsInt)->or($keyAndValAreEqualInts))
            ->makeTuple()
            ->toArray();
        
        //then
        self::assertSame([
            [0, 'a'],
            ['b', 3],
            [2, 2],
            [1, 'a'],
            [1, 'b'],
        ], $actual);
    }
    
    public function test_filter_stream_with_xor_filter(): void
    {
        //given
        $keys =   [0,  'b', 2, 1,   'a', 1,  'b',   'c', 3];
        $values = ['a', 3,  2, 'a', 'b', 'b', true, 'a', 1];
        
        $producer = Producers::combinedFrom($keys, $values);
        
        //when
        $actual = Stream::from($producer)
            ->filter(Filters::OR('is_int', 'is_string'), Check::BOTH)
            ->filter(Filters::isInt(Check::VALUE)->xor(Filters::isInt(Check::KEY)))
            ->makeTuple()
            ->toArray();
        
        //then
        self::assertSame([
            [0, 'a'],
            ['b', 3],
            [1, 'a'],
            [1, 'b'],
        ], $actual);
    }
    
    public function test_filter_stream_with_xnor_filter(): void
    {
        //given
        $keys =   [0,  'b', 2, 1,   'a', 1,  'b',   'c', 3];
        $values = ['a', 3,  2, 'a', 'b', 'b', true, 'a', 1];
        
        $producer = Producers::combinedFrom($keys, $values);
        
        //when
        $actual = Stream::from($producer)
            ->filter(Filters::OR('is_int', 'is_string'), Check::BOTH)
            ->filter(Filters::isInt(Check::VALUE)->xnor(Filters::isInt(Check::KEY)))
            ->makeTuple()
            ->toArray();
        
        //then
        self::assertSame([
            [2, 2],
            ['a', 'b'],
            ['c', 'a'],
            [3, 1],
        ], $actual);
    }
    
    public function test_filter_stream_with_andNot_filter(): void
    {
        //given
        $keys =   [0,  'b', 2, 1,   'a', 1,  'b',   'c', 3];
        $values = ['a', 3,  2, 'a', 'b', 'b', true, 'a', 1];
        
        $producer = Producers::combinedFrom($keys, $values);
        
        //when
        $actual = Stream::from($producer)
            ->filter(Filters::isString(Check::KEY)->andNot(Filters::isBool(Check::VALUE)))
            ->makeTuple()
            ->toArray();
        
        //then
        self::assertSame([
            ['b', 3],
            ['a', 'b'],
            ['c', 'a'],
        ], $actual);
    }
    
    public function test_filter_stream_with_orNot_filter(): void
    {
        //given
        $keys =   [0,  'b', 2, 1,   'a', 1,  'b',   'c', 3];
        $values = ['a', 3,  2, 'a', 'b', 'b', true, 'a', 1];
        
        $producer = Producers::combinedFrom($keys, $values);
        
        //when
        $actual = Stream::from($producer)
            ->filter(Filters::isString(Check::KEY)->orNot(Filters::isString(Check::VALUE)))
            ->makeTuple()
            ->toArray();
        
        //then
        self::assertSame([
            ['b', 3],
            [2, 2],
            ['a', 'b'],
            ['b', true],
            ['c', 'a'],
            [3, 1],
        ], $actual);
    }
    
    public function test_filter_both_with_xor(): void
    {
        //(v < 0 XOR isEven(v)) && (k < 0 XOR isEven(k))
        $actual = Stream::from([-5 => 3, 0 => 3, -1 => 4, 2 => 6, 1 => 2, -2 => 2, 4 => -3])
            ->filter(Filters::lessThan(0)->xor(Filters::number()->isEven()), Check::BOTH)
            ->toArrayAssoc();
        
        self::assertSame([
            -1 => 4,
            2 => 6,
            4 => -3,
        ], $actual);
    }
    
    public function test_filter_any_with_xor(): void
    {
        //(v < 1 XOR isOdd(v)) || (k < 1 XOR isOdd(k))
        $actual = Stream::from([-5 => 3, 0 => 3, -1 => 4, 2 => 6, 1 => 2, -2 => 2, 4 => -3])
            ->filter(Filters::lessThan(1)->xor(Filters::number()->isOdd()), Check::ANY)
            ->toArrayAssoc();
        
        self::assertSame([
            -5 => 3,
            0 => 3,
            1 => 2,
            -2 => 2,
        ], $actual);
    }
    
    public function test_filter_both_with_xnor(): void
    {
        //(v < 0 XNOR isEven(v)) && (k < 0 XNOR isEven(k))
        $actual = Stream::from([-4 => 3, -2 => 3, -1 => 4, 2 => 6, 1 => 3, 4 => -3])
            ->filter(Filters::lessThan(0)->xnor(Filters::number()->isEven()), Check::BOTH)
            ->toArrayAssoc();
        
        self::assertSame([
            -4 => 3,
            -2 => 3,
            1 => 3,
        ], $actual);
    }
    
    public function test_filter_any_with_xnor(): void
    {
        //(v < 1 XNOR isOdd(v)) || (k < 1 XNOR isOdd(k))
        $actual = Stream::from([-4 => 3, -2 => 2, -1 => 4, 2 => 6, 1 => 3, 4 => -3])
            ->filter(Filters::lessThan(1)->xnor(Filters::number()->isOdd()), Check::ANY)
            ->toArrayAssoc();
        
        self::assertSame([
            -2 => 2,
            -1 => 4,
            2 => 6,
            4 => -3,
        ], $actual);
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
            ->fork(Discriminators::evenOdd(), Stream::empty()->collect(true));
        
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
        
        $stats = Stream::from($degrees)->reduce(Reducers::basicStats());
        
        self::assertSame([
            'count' => \count($degrees),
            'min' => \min($degrees),
            'max' => \max($degrees),
            'sum' => \array_sum($degrees),
            'avg' => \array_sum($degrees) / \count($degrees),
        ], $stats->get());
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
            ->categorize(Discriminators::byKey())
            ->map(Reducers::basicStats(2))
            ->map(Mappers::extract(['min', 'avg', 'max']))
            ->map(Mappers::concat(';'))
            ->sort(By::key())
            ->toArrayAssoc();
            
        self::assertSame([
            'A' => '13.2;15.67;18.2',
            'B' => '13.8;14.65;15.5',
            'C' => '9.6;11.9;14.2',
            'D' => '12.9;15.43;19.3',
            'E' => '15.5;15.5;15.5',
        ], $actual);
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
            ->map(Mappers::extract(['min', 'avg', 'max']))
            ->map(Mappers::concat(';'))
            ->segregate(null, false, By::key())
            ->flat(1)
            ->toArrayAssoc();
            
        self::assertSame([
            'A' => '13.2;15.67;18.2',
            'B' => '13.8;14.65;15.5',
            'C' => '9.6;11.9;14.2',
            'D' => '12.9;15.43;19.3',
            'E' => '15.5;15.5;15.5',
        ], $actual);
    }
    
    /**
     * @dataProvider getDataForTestUntilWithFilterInVariousWays
     */
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
        yield [$data->stream()->until(Filters::NOT(Filters::isString(Check::BOTH)))];
        
        yield [$data->stream()->until(Filters::isInt(Check::BOTH))];
        yield [$data->stream()->while(Filters::NOT(Filters::isInt(Check::ANY)))];
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
        $actual = Stream::from([-2 => 5, 2 => 7, 'a' => 3, 7 => 1, 3 => -1, 0 => 0, 1 => 3, 9 => 5])
            ->filter(Filters::NOT(Filters::isString(Check::KEY))->and(Filters::isInt(Check::VALUE)))
            ->filter(Filters::greaterThan(0))
            ->omit(Filters::lessThan(0), Check::KEY)
            ->filter(Filters::number()->isOdd(), Check::BOTH)
            ->without([1, 3, 5, 7], Check::ANY);
        
        self::assertSame([9 => 5], $actual->toArrayAssoc());
    }
}