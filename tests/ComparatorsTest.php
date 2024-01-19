<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Comparator\Comparators;
use FiiSoft\Jackdaw\Comparator\Comparison\Compare;
use FiiSoft\Jackdaw\Comparator\Comparison\Comparer\ComparerFactory;
use FiiSoft\Jackdaw\Comparator\Comparison\Comparer\Single\AssocComparer;
use FiiSoft\Jackdaw\Comparator\Comparison\Comparison;
use FiiSoft\Jackdaw\Comparator\Exception\ComparatorExceptionFactory;
use FiiSoft\Jackdaw\Comparator\ItemComparator\ItemComparatorFactory;
use FiiSoft\Jackdaw\Comparator\Sorting\By;
use FiiSoft\Jackdaw\Comparator\Sorting\Key;
use FiiSoft\Jackdaw\Comparator\Sorting\Sorting;
use FiiSoft\Jackdaw\Comparator\Sorting\Value;
use FiiSoft\Jackdaw\Comparator\ValueKeyCombined\ValueKeyComparator;
use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Item;
use PHPUnit\Framework\TestCase;

final class ComparatorsTest extends TestCase
{
    public function test_getAdapter_throws_exception_on_invalid_argument(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('comparator'));
        
        Comparators::getAdapter(15);
    }
    
    public function test_GenericComparator_throws_exception_when_callable_accepts_wrong_number_of_arguments(): void
    {
        $this->expectExceptionObject(ComparatorExceptionFactory::invalidParamComparator(3));
        
        Comparators::getAdapter(static fn($v, $k, $n): bool => true);
    }
    
    public function test_MultiComparator_throws_exception_when_list_of_comparators_is_empty(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('comparators'));
        
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
    
    public function test_GenericComparator_throws_exception_when_wrong_callable_is_used_1(): void
    {
        $this->expectExceptionObject(ComparatorExceptionFactory::cannotCompareTwoValues());
        
        Comparators::getAdapter(static fn($a, $b, $c, $d): bool => true)->compare(1, 2);
    }
    
    public function test_FieldsComparator(): void
    {
        self::assertSame(0, Comparators::fields(['id'])->compare(['id' => 1], ['id' => 1]));
    }
    
    public function test_FieldsComparator_throws_exception_when_fields_is_empty(): void
    {
        $this->expectExceptionObject(ComparatorExceptionFactory::paramFieldsCannotBeEmpty());
        
        Comparators::fields([]);
    }
    
    public function test_FieldsComparator_throws_exception_when_fields_contains_invalid_value(): void
    {
        $this->expectExceptionObject(ComparatorExceptionFactory::paramFieldsIsInvalid());
        
        Comparators::fields([true]);
    }
    
    public function test_FieldsComparator_compareAssoc_is_not_implemented_and_cannot_be_called(): void
    {
        $this->expectExceptionObject(ComparatorExceptionFactory::compareAssocIsNotImplemented());
        
        Comparators::fields(['a'])->compareAssoc(1, 2, 3, 4);
    }
    
    public function test_SizeComparator_can_compare_number_of_elements_in_arrays(): void
    {
        $comparator = Comparators::size();
        
        self::assertSame(0, $comparator->compare([5, 2], [3, 6]));
        self::assertLessThan(0, $comparator->compare([5, 2], [3, 6, 1]));
        self::assertGreaterThan(0, $comparator->compare([5, 2, 1], [3, 6]));
    }
    
    public function test_LengthComparator_can_compare_length_of_strings(): void
    {
        $comparator = Comparators::length();
        
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
    
    public function test_SizeComparator_can_compare_values_and_keys(): void
    {
        $comparator = Comparators::size();
        
        self::assertSame(0, $comparator->compareAssoc([1,2,3], [4,5,6], 0, 0));
        self::assertGreaterThan(0, $comparator->compareAssoc([1,2,3], [4,5,6], 1, 0));
        self::assertLessThan(0, $comparator->compareAssoc([1,2,3], [4,5,6,7], 1, 0));
        self::assertLessThan(0, $comparator->compareAssoc([1,2,3], [4,5,6], 1, 2));
        self::assertGreaterThan(0, $comparator->compareAssoc([1,2,3,4], [5,6,7], 1, 2));
    }
    
    public function test_LengthComparator_can_compare_values_and_keys(): void
    {
        $comparator = Comparators::length();
        
        self::assertSame(0, $comparator->compareAssoc('trg', 'rsh', 0, 0));
        self::assertGreaterThan(0, $comparator->compareAssoc('trg', 'rsh', 1, 0));
        self::assertLessThan(0, $comparator->compareAssoc('trg', 'rsha', 1, 0));
        self::assertLessThan(0, $comparator->compareAssoc('trg', 'rsh', 1, 2));
        self::assertGreaterThan(0, $comparator->compareAssoc('atrg', 'rsh', 1, 2));
    }
    
    /**
     * @dataProvider getDataForTestItemComparator
     */
    public function test_ItemComparator(Sorting $sorting, array $first, array $second, int $expected): void
    {
        $item1 = new Item($first[0], $first[1]);
        $item2 = new Item($second[0], $second[1]);
        
        self::assertSame(
            $expected,
            ItemComparatorFactory::getForSorting($sorting)->compare($item1, $item2)
        );
    }
    
    public static function getDataForTestItemComparator(): \Generator
    {
        //sorting, first(k,v), second(k,v), expected
        yield [By::valueAsc(), [0, 1], [2, 1], 0];
        yield [By::valueAsc(), [0, 1], [2, 2], -1];
        yield [By::valueAsc(), [0, 3], [2, 2], 1];
        
        yield [By::valueDesc(), [0, 1], [2, 1], 0];
        yield [By::valueDesc(), [0, 1], [2, 2], 1];
        yield [By::valueDesc(), [0, 3], [2, 2], -1];
        
        yield [By::keyAsc(), [0, 1], [0, 1], 0];
        yield [By::keyAsc(), [0, 1], [2, 1], -1];
        yield [By::keyAsc(), [3, 1], [2, 1], 1];
        
        yield [By::keyDesc(), [0, 1], [0, 1], 0];
        yield [By::keyDesc(), [0, 1], [2, 1], 1];
        yield [By::keyDesc(), [3, 1], [2, 1], -1];
        
        yield [By::bothAsc(), [0, 1], [0, 1], 0];
        yield [By::bothAsc(), [0, 1], [0, 2], -1];
        yield [By::bothAsc(), [0, 2], [1, 2], -1];
        yield [By::bothAsc(), [1, 2], [0, 2], 1];
        yield [By::bothAsc(), [1, 2], [0, 1], 1];
        yield [By::bothAsc(), [1, 2], [1, 1], 1];
        
        yield [By::bothDesc(), [0, 1], [0, 1], 0];
        yield [By::bothDesc(), [0, 1], [0, 2], 1];
        yield [By::bothDesc(), [0, 2], [1, 2], 1];
        yield [By::bothDesc(), [1, 2], [0, 2], -1];
        yield [By::bothDesc(), [1, 2], [0, 1], -1];
        yield [By::bothDesc(), [1, 2], [1, 1], -1];
        
        yield [By::valueAsc('strnatcmp'), [0, 'a'], [1, 'a'], 0];
        yield [By::valueAsc('strnatcmp'), [0, 'a'], [1, 'b'], -1];
        yield [By::valueAsc('strnatcmp'), [0, 'b'], [1, 'a'], 1];
        
        yield [By::valueDesc('strnatcmp'), [0, 'a'], [1, 'a'], 0];
        yield [By::valueDesc('strnatcmp'), [0, 'a'], [1, 'b'], 1];
        yield [By::valueDesc('strnatcmp'), [0, 'b'], [1, 'a'], -1];

        yield [By::keyAsc('strnatcmp'), ['a', 1], ['a', 1], 0];
        yield [By::keyAsc('strnatcmp'), ['a', 1], ['b', 1], -1];
        yield [By::keyAsc('strnatcmp'), ['b', 1], ['a', 1], 1];
        
        yield [By::keyDesc('strnatcmp'), ['a', 1], ['a', 1], 0];
        yield [By::keyDesc('strnatcmp'), ['a', 1], ['b', 1], 1];
        yield [By::keyDesc('strnatcmp'), ['b', 1], ['a', 1], -1];
        
        yield [By::bothAsc('strnatcmp'), ['a', 'b'], ['a', 'b'], 0];
        yield [By::bothAsc('strnatcmp'), ['a', 'b'], ['a', 'c'], -1];
        yield [By::bothAsc('strnatcmp'), ['a', 'c'], ['b', 'c'], -1];
        yield [By::bothAsc('strnatcmp'), ['b', 'c'], ['a', 'c'], 1];
        yield [By::bothAsc('strnatcmp'), ['b', 'c'], ['a', 'b'], 1];
        yield [By::bothAsc('strnatcmp'), ['b', 'c'], ['b', 'b'], 1];
        
        yield [By::bothDesc('strnatcmp'), ['a', 'b'], ['a', 'b'], 0];
        yield [By::bothDesc('strnatcmp'), ['a', 'b'], ['a', 'c'], 1];
        yield [By::bothDesc('strnatcmp'), ['a', 'c'], ['b', 'c'], 1];
        yield [By::bothDesc('strnatcmp'), ['b', 'c'], ['a', 'c'], -1];
        yield [By::bothDesc('strnatcmp'), ['b', 'c'], ['a', 'b'], -1];
        yield [By::bothDesc('strnatcmp'), ['b', 'c'], ['b', 'b'], -1];
    }
    
    public function test_sort_by_custom_reversed_assoc(): void
    {
        $comparator = ItemComparatorFactory::getForSorting(By::assocDesc(
            static fn($v1, $v2, $k1, $k2): int => $v1 <=> $v2 ?: $k1 <=> $k2
        ));
        
        $first = new Item(2, 5);
        $second = new Item(1, 5);
        
        $actual = $comparator->compare($first, $second);
        
        self::assertSame(-1, $actual);
    }
    
    public function test_ValueKeyComparator_throws_exception_when_method_compare_is_called(): void
    {
        //Arrange
        $comparator = new class extends ValueKeyComparator {
            public function compareAssoc($value1, $value2, $key1, $key2): int {
                return 0;
            }
        };
        
        //Assert
        $this->expectExceptionObject(ComparatorExceptionFactory::cannotCompareOnlyValues($comparator));
        
        //Act
        $comparator->compare(1, 2);
    }
    
    public function test_assoc_Comparator_wrapped_by_nonassoc_Sorting_throws_exception_1(): void
    {
        $this->expectExceptionObject(ComparatorExceptionFactory::invalidSortingCallable('value'));
        
        By::value(static fn(string $v1, string $v2, int $k1, int $k2): int => $v1 <=> $v2 ?: $k2 <=> $k1);
    }
    
    public function test_assoc_Comparator_wrapped_by_nonassoc_Sorting_throws_exception_2(): void
    {
        $this->expectExceptionObject(ComparatorExceptionFactory::invalidSortingCallable('key'));
        
        By::key(static fn(string $v1, string $v2, int $k1, int $k2): int => $v1 <=> $v2 ?: $k2 <=> $k1);
    }
    
    public function test_assoc_Comparator_wrapped_by_nonassoc_Comparison_throws_exception_1(): void
    {
        $this->expectExceptionObject(ComparatorExceptionFactory::wrongComparisonCallable(Check::VALUE));
        
        Compare::values(static fn(string $v1, string $v2, int $k1, int $k2): int => $v1 <=> $v2 ?: $k2 <=> $k1);
    }
    
    public function test_assoc_Comparator_wrapped_by_nonassoc_Comparison_throws_exception_2(): void
    {
        $this->expectExceptionObject(ComparatorExceptionFactory::wrongComparisonCallable(Check::KEY));
        
        Compare::keys(static fn(string $v1, string $v2, int $k1, int $k2): int => $v1 <=> $v2 ?: $k2 <=> $k1);
    }
    
    public function test_DoubleSorting_specification_throws_exception_when_first_spec_is_not_valid(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('first'));
        
        Sorting::double(Sorting::create(false, null, Check::BOTH), Sorting::create(false, null, Check::KEY));
    }
    
    public function test_DoubleSorting_specification_throws_exception_when_second_spec_is_not_valid(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('second'));
        
        Sorting::double(Sorting::create(false, null, Check::VALUE), Sorting::create(false, null, Check::ANY));
    }
    
    public function test_DoubleSorting_spec_throws_exception_when_mode_of_first_and_second_specs_are_the_same(): void
    {
        $this->expectExceptionObject(ComparatorExceptionFactory::sortingsCannotBeTheSame());
        
        Sorting::double(Sorting::create(false, null, Check::VALUE), Sorting::create(false, null, Check::VALUE));
    }
    
    public function test_compare_in_various_modes(): void
    {
        //given
        $first = new Item(3, 'a');
        $second = new Item(5, 'a');
        
        //when
        $comparer = ComparerFactory::createComparer(Compare::values());
        //then
        self::assertFalse($comparer->areDifferent($first, $second));
        
        //when
        $comparer = ComparerFactory::createComparer(Compare::keys());
        //then
        self::assertTrue($comparer->areDifferent($first, $second));
        
        //when
        $comparer = ComparerFactory::createComparer(Compare::valuesAndKeysSeparately());
        //then
        self::assertFalse($comparer->areDifferent($first, $second));
        
        //when
        $comparer = ComparerFactory::createComparer(Compare::bothValuesAndKeysTogether());
        //then
        self::assertTrue($comparer->areDifferent($first, $second));
        
        //when
        $comparer = ComparerFactory::createComparer(Compare::assoc());
        //then
        self::assertTrue($comparer->areDifferent($first, $second));
    }
    
    public function test_ValueKeyComparator_is_properly_recognised_by_Comparison(): void
    {
        //given
        $comparator = new class extends ValueKeyComparator {
            public function compareAssoc($value1, $value2, $key1, $key2): int {
                return 0;
            }
        };
        
        //when
        $comparer = ComparerFactory::createComparer(Compare::values($comparator));
        
        //then
        self::assertInstanceOf(AssocComparer::class, $comparer);
    }
    
    public function test_DoubleComparison_throws_exception_when_param_mode_is_invalid(): void
    {
        $this->expectExceptionObject(Check::invalidModeException(Check::VALUE));
        
        Comparison::double(Check::VALUE);
    }
    
    public function test_getAdapter_can_return_comparator_wrapped_by_Comparable_instance(): void
    {
        //given
        $comparator = Comparators::default();
        $comparison = Compare::values($comparator);
        
        //when
        $adapter = Comparators::getAdapter($comparison);
        
        //then
        self::assertSame($comparator, $adapter);
    }
    
    public function test_ValueKeyComparator_always_returns_itself_from_method_comparator_with_mode_BOTH(): void
    {
        $comparator = Sorting::double(Value::asc(), Key::asc())->comparator();
        
        self::assertSame($comparator, $comparator->comparator());
        self::assertSame(Check::BOTH, $comparator->mode());
    }
    
    public function test_GenericComparator_always_returns_itself_from_method_comparator(): void
    {
        $comparator = Comparators::getAdapter('strcmp');
        
        self::assertSame($comparator, $comparator->comparator());
    }
    
    public function test_GenericComparator_returns_mode_dependend_on_wrapped_callback(): void
    {
        self::assertSame(Check::VALUE, Comparators::getAdapter('strcmp')->mode());
        
        self::assertSame(Check::BOTH, Comparators::getAdapter(static fn($a, $b, $c, $d) => -1)->mode());
    }
    
    public function test_BaseComparator_mode_is_VALUE(): void
    {
        $comparator = Comparators::default();
        
        self::assertSame(Check::VALUE, $comparator->mode());
    }
    
    public function test_BaseComparator_always_returns_itself_from_method_comparator(): void
    {
        $comparator = Comparators::default();
        
        self::assertSame($comparator, $comparator->comparator());
    }
}