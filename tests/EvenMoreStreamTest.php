<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Comparator\Comparators;
use FiiSoft\Jackdaw\Comparator\Comparison\Compare;
use FiiSoft\Jackdaw\Comparator\Sorting\By;
use FiiSoft\Jackdaw\Mapper\Mappers;
use FiiSoft\Jackdaw\Producer\Producers;
use FiiSoft\Jackdaw\Stream;
use PHPUnit\Framework\TestCase;

final class EvenMoreStreamTest extends TestCase
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
    
    public function test_sort_by_size_asc(): void
    {
        self::assertSame(
            ['sud', 'tsgad', 'ytbebafdof'],
            Stream::from(['tsgad', 'ytbebafdof', 'sud'])->sort(By::sizeAsc())->toArray()
        );
    }
    
    public function test_sort_by_size_desc(): void
    {
        self::assertSame(
            ['ytbebafdof', 'tsgad', 'sud'],
            Stream::from(['tsgad', 'ytbebafdof', 'sud'])->sort(By::sizeDesc())->toArray()
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
            ->mapField('age', Producers::collatz(100))
            ->toArray();
        
        $expected = [
            ['id' => 2, 'name' => 'Sue', 'age' => 100],
            ['id' => 9, 'name' => 'Chris', 'age' => 50],
            ['id' => 6, 'name' => 'Joanna', 'age' => 25],
            ['id' => 5, 'name' => 'Chris', 'age' => 76],
            ['id' => 7, 'name' => 'Sue', 'age' => 38],
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
//            [[], []],
//            [[1], [1]],
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
}