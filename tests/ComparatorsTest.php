<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Comparator\Comparators;
use FiiSoft\Jackdaw\Comparator\ItemComparator\ItemComparatorFactory;
use FiiSoft\Jackdaw\Comparator\ValueKeyCombined\ValueKeyComparator;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Item;
use PHPUnit\Framework\TestCase;

final class ComparatorsTest extends TestCase
{
    public function test_getAdapter_throws_exception_on_invalid_argument(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        Comparators::getAdapter(15);
    }
    
    public function test_GenericComparator_throws_exception_when_callable_accepts_wrong_number_of_arguments(): void
    {
        $this->expectException(\LogicException::class);
        
        Comparators::generic(static fn($v, $k, $n): bool => true);
    }
    
    public function test_MultiComparator_throws_exception_when_list_of_comparators_is_empty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param comparators');
        
        Comparators::multi();
    }
    
    public function test_MultiComparator_compare(): void
    {
        $comp = Comparators::multi(
            static fn(array $a, array $b): int => $a['foo'] <=> $b['foo'],
            static fn(array $a, array $b): int => $a['bar'] <=> $b['bar'],
        );
        
        self::assertSame(0, $comp->compare(
            ['foo' => 5, 'bar' => 7],
            ['foo' => 5, 'bar' => 7],
        ));
        
        self::assertGreaterThan(0, $comp->compare(
            ['foo' => 7, 'bar' => 7],
            ['foo' => 5, 'bar' => 7],
        ));
        
        self::assertGreaterThan(0, $comp->compare(
            ['foo' => 5, 'bar' => 9],
            ['foo' => 5, 'bar' => 7],
        ));
        
        self::assertLessThan(0, $comp->compare(
            ['foo' => 5, 'bar' => 7],
            ['foo' => 9, 'bar' => 7],
        ));
        
        self::assertLessThan(0, $comp->compare(
            ['foo' => 5, 'bar' => 3],
            ['foo' => 5, 'bar' => 7],
        ));
    }
    
    public function test_MultiComparator_compareAssoc(): void
    {
        $comp = Comparators::multi(
            static fn(array $a, array $b, string $k1, string $k2): int => $a['foo'] <=> $b['foo'],
            static fn(array $a, array $b, string $k1, string $k2): int => $a['bar'] <=> $b['bar'],
            static fn(array $a, array $b, string $k1, string $k2): int => \strlen($k1) <=> \strlen($k2),
        );
        
        self::assertSame(0, $comp->compareAssoc(
            ['foo' => 5, 'bar' => 7],
            ['foo' => 5, 'bar' => 7],
            'zo',
            'gh',
        ));
        
        self::assertGreaterThan(0, $comp->compareAssoc(
            ['foo' => 9, 'bar' => 7],
            ['foo' => 5, 'bar' => 7],
            'zo',
            'gh',
        ));
        
        self::assertGreaterThan(0, $comp->compareAssoc(
            ['foo' => 5, 'bar' => 9],
            ['foo' => 5, 'bar' => 7],
            'zo',
            'gh',
        ));
        
        self::assertGreaterThan(0, $comp->compareAssoc(
            ['foo' => 5, 'bar' => 7],
            ['foo' => 5, 'bar' => 7],
            'zon',
            'gh',
        ));
        
        self::assertLessThan(0, $comp->compareAssoc(
            ['foo' => 3, 'bar' => 7],
            ['foo' => 5, 'bar' => 7],
            'zo',
            'gh',
        ));
        
        self::assertLessThan(0, $comp->compareAssoc(
            ['foo' => 5, 'bar' => 5],
            ['foo' => 5, 'bar' => 7],
            'zo',
            'gh',
        ));
        
        self::assertLessThan(0, $comp->compareAssoc(
            ['foo' => 5, 'bar' => 7],
            ['foo' => 5, 'bar' => 7],
            'z',
            'gh',
        ));
    }
    
    public function test_MultiComparator_accepts_other_comparators(): void
    {
        $comp = Comparators::multi(
            static fn(int $v1, int $v2, int $k2, int $k1): int => $k1 <=> $k2,
        );
        
        $comp->addComparators([Comparators::getAdapter(null)]);
        
        self::assertSame(0, $comp->compareAssoc(3, 3, 5, 5));
        self::assertLessThan(0, $comp->compareAssoc(3, 3, 5, 2));
        
        self::assertGreaterThan(0, $comp->compareAssoc(3, 3, 2, 5));
        self::assertGreaterThan(0, $comp->compareAssoc(4, 3, 2, 5));
        self::assertGreaterThan(0, $comp->compareAssoc(3, 4, 2, 5));
    }
    
    public function test_GenericComparator_throws_exception_when_wrong_callable_is_used(): void
    {
        try {
            Comparators::generic(static fn($a, $b, $c, $d): bool => true)->compare(1, 2);
            self::fail('Expected exception was not thrown');
        } catch (\LogicException $e) {
            //ok
        }
        
        try {
            Comparators::generic(static fn($a, $b, $c): bool => true)->compareAssoc(1, 2, 3, 4);
            self::fail('Expected exception was not thrown');
        } catch (\LogicException $e) {
            //ok
        }
        
        self::assertTrue(true);
    }
    
    public function test_SortBy_throws_exception_when_value_is_not_array(): void
    {
        $comparator = Comparators::sortBy(['id']);
    
        try {
            $comparator->compare(5, [6]);
            self::fail('Expected exception was not thrown');
        } catch (\LogicException $e) {
            //ok
        }
    
        try {
            $comparator->compare([6], 5);
            self::fail('Expected exception was not thrown');
        } catch (\LogicException $e) {
            //ok
        }
        
        self::assertTrue(true);
    }
    
    public function test_SortBy(): void
    {
        self::assertSame(0, Comparators::sortBy(['id'])->compare(['id' => 1], ['id' => 1]));
    }
    
    public function test_SortBy_throws_exception_when_fields_is_empty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        Comparators::sortBy([]);
    }
    
    public function test_SortBy_throws_exception_when_fields_contains_invalid_value(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        Comparators::sortBy([true]);
    }
    
    public function test_SortBy_compareAssoc_is_not_implemented_and_cannot_be_called(): void
    {
        $this->expectException(\LogicException::class);
        
        Comparators::sortBy(['a'])->compareAssoc(1, 2, 3, 4);
    }
    
    public function test_SizeComparator_can_compare_number_of_elements_in_arrays(): void
    {
        $comparator = Comparators::size();
        
        self::assertSame(0, $comparator->compare([5, 2], [3, 6]));
        self::assertLessThan(0, $comparator->compare([5, 2], [3, 6, 1]));
        self::assertGreaterThan(0, $comparator->compare([5, 2, 1], [3, 6]));
    }
    
    public function test_SizeComparator_can_compare_length_of_strings(): void
    {
        $comparator = Comparators::size();
        
        self::assertSame(0, $comparator->compare('agd', 'trh'));
        self::assertLessThan(0, $comparator->compare('agd', 'trhb'));
        self::assertGreaterThan(0, $comparator->compare('agdr', 'trh'));
    }
    
    public function test_SizeComparator_can_compare_Countable_objects(): void
    {
        $comparator = Comparators::size();
        
        self::assertSame(0, $comparator->compare(new \ArrayObject([4,7]), new \ArrayObject([2,9])));
        self::assertLessThan(0, $comparator->compare(new \ArrayObject([4,7]), new \ArrayObject([2,9,7])));
        self::assertGreaterThan(0, $comparator->compare(new \ArrayObject([2, 4,7]), new \ArrayObject([2,9])));
    }
    
    public function test_SizeComparator_throws_exception_when_cannot_compare_values(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot compute size of integer');
        
        Comparators::size()->compare(15, 15);
    }
    
    public function test_SizeComparator_can_compare_values_and_keys(): void
    {
        $comparator = Comparators::size();
        
        self::assertSame(0, $comparator->compareAssoc('trg', 'rsh', 0, 0));
        self::assertGreaterThan(0, $comparator->compareAssoc('trg', 'rsh', 1, 0));
        self::assertLessThan(0, $comparator->compareAssoc('trg', 'rsha', 1, 0));
        self::assertLessThan(0, $comparator->compareAssoc('trg', 'rsh', 1, 2));
        self::assertGreaterThan(0, $comparator->compareAssoc('atrg', 'rsh', 1, 2));
    }
    
    /**
     * @dataProvider getDataForTestItemComparator
     */
    public function test_ItemComparator(
        int $mode, bool $reversed, $comparator, array $first, array $second, int $expected
    ): void
    {
        $item1 = new Item($first[0], $first[1]);
        $item2 = new Item($second[0], $second[1]);
        
        self::assertSame(
            $expected,
            ItemComparatorFactory::getFor($mode, $reversed, $comparator)->compare($item1, $item2)
        );
    }
    
    public static function getDataForTestItemComparator(): \Generator
    {
        yield [Check::VALUE, false, null, [0, 1], [2, 1], 0];
        yield [Check::VALUE, false, null, [0, 1], [2, 2], -1];
        yield [Check::VALUE, false, null, [0, 3], [2, 2], 1];
        
        yield [Check::VALUE, true, null, [0, 1], [2, 1], 0];
        yield [Check::VALUE, true, null, [0, 1], [2, 2], 1];
        yield [Check::VALUE, true, null, [0, 3], [2, 2], -1];
        
        yield [Check::KEY, false, null, [0, 1], [0, 1], 0];
        yield [Check::KEY, false, null, [0, 1], [2, 1], -1];
        yield [Check::KEY, false, null, [3, 1], [2, 1], 1];
        
        yield [Check::KEY, true, null, [0, 1], [0, 1], 0];
        yield [Check::KEY, true, null, [0, 1], [2, 1], 1];
        yield [Check::KEY, true, null, [3, 1], [2, 1], -1];
        
        yield [Check::BOTH, false, null, [0, 1], [0, 1], 0];
        yield [Check::BOTH, false, null, [0, 1], [0, 2], -1];
        yield [Check::BOTH, false, null, [0, 2], [1, 2], -1];
        yield [Check::BOTH, false, null, [1, 2], [0, 2], 1];
        yield [Check::BOTH, false, null, [1, 2], [0, 1], 1];
        yield [Check::BOTH, false, null, [1, 2], [1, 1], 1];
        
        yield [Check::BOTH, true, null, [0, 1], [0, 1], 0];
        yield [Check::BOTH, true, null, [0, 1], [0, 2], 1];
        yield [Check::BOTH, true, null, [0, 2], [1, 2], 1];
        yield [Check::BOTH, true, null, [1, 2], [0, 2], -1];
        yield [Check::BOTH, true, null, [1, 2], [0, 1], -1];
        yield [Check::BOTH, true, null, [1, 2], [1, 1], -1];
        
        yield [Check::VALUE, false, 'strnatcmp', [0, 'a'], [1, 'a'], 0];
        yield [Check::VALUE, false, 'strnatcmp', [0, 'a'], [1, 'b'], -1];
        yield [Check::VALUE, false, 'strnatcmp', [0, 'b'], [1, 'a'], 1];
        
        yield [Check::VALUE, true, 'strnatcmp', [0, 'a'], [1, 'a'], 0];
        yield [Check::VALUE, true, 'strnatcmp', [0, 'a'], [1, 'b'], 1];
        yield [Check::VALUE, true, 'strnatcmp', [0, 'b'], [1, 'a'], -1];

        yield [Check::KEY, false, 'strnatcmp', ['a', 1], ['a', 1], 0];
        yield [Check::KEY, false, 'strnatcmp', ['a', 1], ['b', 1], -1];
        yield [Check::KEY, false, 'strnatcmp', ['b', 1], ['a', 1], 1];
        
        yield [Check::KEY, true, 'strnatcmp', ['a', 1], ['a', 1], 0];
        yield [Check::KEY, true, 'strnatcmp', ['a', 1], ['b', 1], 1];
        yield [Check::KEY, true, 'strnatcmp', ['b', 1], ['a', 1], -1];

        yield [Check::BOTH, false, 'strnatcmp', ['a', 'b'], ['a', 'b'], 0];
        yield [Check::BOTH, false, 'strnatcmp', ['a', 'b'], ['a', 'c'], -1];
        yield [Check::BOTH, false, 'strnatcmp', ['a', 'c'], ['b', 'c'], -1];
        yield [Check::BOTH, false, 'strnatcmp', ['b', 'c'], ['a', 'c'], 1];
        yield [Check::BOTH, false, 'strnatcmp', ['b', 'c'], ['a', 'b'], 1];
        yield [Check::BOTH, false, 'strnatcmp', ['b', 'c'], ['b', 'b'], 1];
    
        yield [Check::BOTH, true, 'strnatcmp', ['a', 'b'], ['a', 'b'], 0];
        yield [Check::BOTH, true, 'strnatcmp', ['a', 'b'], ['a', 'c'], 1];
        yield [Check::BOTH, true, 'strnatcmp', ['a', 'c'], ['b', 'c'], 1];
        yield [Check::BOTH, true, 'strnatcmp', ['b', 'c'], ['a', 'c'], -1];
        yield [Check::BOTH, true, 'strnatcmp', ['b', 'c'], ['a', 'b'], -1];
        yield [Check::BOTH, true, 'strnatcmp', ['b', 'c'], ['b', 'b'], -1];
    }
    
    public function test_ValueKeyComparator_throws_exception_when_method_compare_is_called(): void
    {
        //Assert
        $this->expectException(\BadMethodCallException::class);
        
        //Arrange
        $comparator = new class extends ValueKeyComparator {
            public function compareAssoc($value1, $value2, $key1, $key2): int {
                return 0;
            }
        };
        
        //Act
        $comparator->compare(1, 2);
    }
}