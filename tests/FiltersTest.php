<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Filter\Length;
use FiiSoft\Jackdaw\Internal\Check;
use PHPUnit\Framework\TestCase;
use stdClass;

final class FiltersTest extends TestCase
{
    public function test_GreaterOrEqual_with_numeric_string(): void
    {
        $filter = Filters::greaterOrEqual(15);
        self::assertTrue($filter->isAllowed('15', 1));
        self::assertTrue($filter->isAllowed('15.0', 1));
        self::assertFalse($filter->isAllowed('14', 1));
        self::assertFalse($filter->isAllowed('14.0', 1));
    }
    
    public function test_GreaterOrEqual_throws_exception_on_not_a_number(): void
    {
        $this->expectException(\LogicException::class);
    
        $filter = Filters::greaterOrEqual(15);
        $filter->isAllowed([], 1);
    }
    
    public function test_GreaterThan_with_numeric_string(): void
    {
        $filter = Filters::greaterThan(15);
        self::assertTrue($filter->isAllowed('16', 1));
        self::assertTrue($filter->isAllowed('16.0', 1));
        self::assertFalse($filter->isAllowed('15', 1));
        self::assertFalse($filter->isAllowed('15.0', 1));
    }
    
    public function test_GreaterThan_throws_exception_on_not_a_number(): void
    {
        $this->expectException(\LogicException::class);
    
        $filter = Filters::greaterThan(15);
        $filter->isAllowed([], 1);
    }
    
    public function test_LessOrEqual_with_numeric_string(): void
    {
        $filter = Filters::lessOrEqual(15);
        self::assertTrue($filter->isAllowed('15', 1));
        self::assertTrue($filter->isAllowed('15.0', 1));
        self::assertFalse($filter->isAllowed('16', 1));
        self::assertFalse($filter->isAllowed('16.0', 1));
    }
    
    public function test_LessOrEqual_throws_exception_on_not_a_number(): void
    {
        $this->expectException(\LogicException::class);
        
        $filter = Filters::lessOrEqual(15);
        $filter->isAllowed([], 1);
    }
    
    public function test_LessThan_with_numeric_string(): void
    {
        $filter = Filters::lessThan(15);
        self::assertTrue($filter->isAllowed('14', 1));
        self::assertTrue($filter->isAllowed('14.0', 1));
        self::assertFalse($filter->isAllowed('15', 1));
        self::assertFalse($filter->isAllowed('15.0', 1));
    }
    
    public function test_LessThan_throws_exception_on_not_a_number(): void
    {
        $this->expectException(\LogicException::class);
        
        $filter = Filters::lessThan(15);
        $filter->isAllowed([], 1);
    }
    
    
    public function test_LessThan_with_integers(): void
    {
        $filter = Filters::lessThan(15);
        self::assertTrue($filter->isAllowed(14, 1));
        self::assertFalse($filter->isAllowed(15, 1));
    }
    
    public function test_NumberFilter_throws_exception_on_invalid_param(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Filters::lessThan('15');
    }
    
    public function test_NumberFilter_can_compare_keys(): void
    {
        $filter = Filters::lessThan(15);
        
        self::assertTrue($filter->isAllowed(30, 10, Check::KEY));
        self::assertFalse($filter->isAllowed(30, 20, Check::KEY));
    }
    
    public function test_NumberFilter_in_both_mode(): void
    {
        $filter = Filters::lessThan(15);
        
        self::assertTrue($filter->isAllowed(12, 10, Check::BOTH));
        self::assertFalse($filter->isAllowed(30, 10, Check::BOTH));
        self::assertFalse($filter->isAllowed(12, 20, Check::BOTH));
    }
    
    public function test_NumberFilter_in_any_mode(): void
    {
        $filter = Filters::lessThan(15);
        
        self::assertTrue($filter->isAllowed(12, 10, Check::ANY));
        self::assertTrue($filter->isAllowed(30, 10, Check::ANY));
        self::assertTrue($filter->isAllowed(12, 20, Check::ANY));
        self::assertFalse($filter->isAllowed(20, 20, Check::ANY));
    }
    
    public function test_NumberFilter_throws_exception_on_invalid_param_mode(): void
    {
        $this->expectException(\InvalidArgumentException::class);
    
        $filter = Filters::lessThan(15);
        $filter->isAllowed(15, 2, 0);
    }
    
    public function test_Equal_can_compare_both_value_and_key(): void
    {
        $filter = Filters::equal(5);
        
        self::assertTrue($filter->isAllowed(5, 5, Check::BOTH));
        self::assertFalse($filter->isAllowed(5, 1, Check::BOTH));
        self::assertFalse($filter->isAllowed(1, 5, Check::BOTH));
    }
    
    public function test_Equal_can_compare_any_value_or_key(): void
    {
        $filter = Filters::equal(5);
        
        self::assertTrue($filter->isAllowed(5, 5, Check::ANY));
        self::assertTrue($filter->isAllowed(5, 1, Check::ANY));
        self::assertTrue($filter->isAllowed(1, 5, Check::ANY));
        self::assertFalse($filter->isAllowed(1, 1, Check::ANY));
    }
    
    public function test_Equal_throws_exception_on_invalid_mode(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $filter = Filters::equal(5);
        $filter->isAllowed(1, 1, 0);
    }
    
    public function test_getAdapter_throws_exception_on_invalid_argument(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        Filters::getAdapter(new stdClass());
    }
    
    public function test_GenericFilter_can_call_callable_without_arguments(): void
    {
        self::assertTrue(Filters::generic(static fn() => true)->isAllowed(1, 1));
        self::assertFalse(Filters::generic(static fn() => false)->isAllowed(1, 1));
    }
    
    public function test_GenericFilter_can_call_callable_with_three_arguments(): void
    {
        $value = null;
        $key = null;
        $mode = null;
        
        $filter = Filters::generic(function ($_value, $_key, $_mode) use (&$value, &$key, &$mode) {
            $value = $_value;
            $key = $_key;
            $mode = $_mode;
            return true;
        });
        
        self::assertTrue($filter->isAllowed(10, 5, 2));
        
        self::assertSame(2, $mode);
        self::assertSame(5, $key);
        self::assertSame(10, $value);
    }
    
    public function test_GenericFilter_can_call_callable_with_two_arguments(): void
    {
        $value = null;
        $key = null;
        
        $filter = Filters::generic(function ($_value, $_key, $_) use (&$value, &$key) {
            $value = $_value;
            $key = $_key;
            return true;
        });
        
        self::assertTrue($filter->isAllowed(10, 5, 2));
        
        self::assertSame(5, $key);
        self::assertSame(10, $value);
    }
    
    public function test_GenericFilter_throws_exception_when_callable_has_unsupported_number_of_arguments(): void
    {
        $this->expectException(\LogicException::class);
        
        $filter = Filters::generic(static fn($a, $b, $c, $d) => true);
        $filter->isAllowed(1, 1);
    }
    
    public function test_GenericFilter_can_compare_key(): void
    {
        $filter = Filters::generic(static fn($key) => $key === 'a');
        
        self::assertFalse($filter->isAllowed(15, 'a', Check::VALUE));
        self::assertTrue($filter->isAllowed(15, 'a', Check::KEY));
    }
    
    public function test_GenericFilter_can_compare_value(): void
    {
        $filter = Filters::generic(static fn($val) => $val === 15);
        
        self::assertTrue($filter->isAllowed(15, 'a', Check::VALUE));
        self::assertFalse($filter->isAllowed(15, 'a', Check::KEY));
    }
    
    public function test_GenericFilter_can_compare_both_value_and_key(): void
    {
        $filter = Filters::generic(static fn($val) => $val === 'a');
        
        self::assertTrue($filter->isAllowed('a', 'a', Check::BOTH));
        self::assertFalse($filter->isAllowed(15, 'a', Check::BOTH));
        self::assertFalse($filter->isAllowed('a', 15, Check::BOTH));
    }
    
    public function test_GenericFilter_can_compare_any_value_or_key(): void
    {
        $filter = Filters::generic(static fn($val) => $val === 'a');
        
        self::assertTrue($filter->isAllowed('a', 'a', Check::ANY));
        self::assertTrue($filter->isAllowed(15, 'a', Check::ANY));
        self::assertTrue($filter->isAllowed('a', 15, Check::ANY));
        self::assertFalse($filter->isAllowed(15, 15, Check::ANY));
    }
    
    public function test_GenericFilter_throws_exception_on_invalid_mode(): void
    {
        $this->expectException(\InvalidArgumentException::class);
    
        $filter = Filters::generic(static fn($val) => $val === 'a');
        $filter->isAllowed(1, 1, 0);
    }
    
    public function test_IsInt_can_check_key(): void
    {
        $filter = Filters::isInt();
        self::assertTrue($filter->isAllowed('a', 5, Check::KEY));
    }
    
    public function test_IsInt_can_check_both_key_and_value(): void
    {
        $filter = Filters::isInt();
        self::assertTrue($filter->isAllowed(5, 5, Check::BOTH));
        self::assertFalse($filter->isAllowed('a', 5, Check::BOTH));
        self::assertFalse($filter->isAllowed(5, 'a', Check::BOTH));
    }
    
    public function test_IsInt_can_check_any_key_or_value(): void
    {
        $filter = Filters::isInt();
        self::assertTrue($filter->isAllowed(5, 5, Check::ANY));
        self::assertTrue($filter->isAllowed('a', 5, Check::ANY));
        self::assertTrue($filter->isAllowed(5, 'a', Check::ANY));
        self::assertFalse($filter->isAllowed('a', 'a', Check::ANY));
    }
    
    public function test_IsInt_throws_exception_on_invalid_mode(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        Filters::isInt()->isAllowed(1, 1, 0);
    }
    
    public function test_IsNumeric_can_check_key(): void
    {
        $filter = Filters::isNumeric();
        self::assertTrue($filter->isAllowed('a', '5', Check::KEY));
    }
    
    public function test_IsNumeric_can_check_both_key_and_value(): void
    {
        $filter = Filters::isNumeric();
        self::assertTrue($filter->isAllowed('5', '5', Check::BOTH));
        self::assertFalse($filter->isAllowed('a', '5', Check::BOTH));
        self::assertFalse($filter->isAllowed('5', 'a', Check::BOTH));
    }
    
    public function test_IsNumeric_can_check_any_key_or_value(): void
    {
        $filter = Filters::isNumeric();
        self::assertTrue($filter->isAllowed('5', '5', Check::ANY));
        self::assertTrue($filter->isAllowed('a', '5', Check::ANY));
        self::assertTrue($filter->isAllowed('5', 'a', Check::ANY));
        self::assertFalse($filter->isAllowed('a', 'a', Check::ANY));
    }
    
    public function test_IsNumeric_throws_exception_on_invalid_mode(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        Filters::isNumeric()->isAllowed(1, 1, 0);
    }
    
    public function test_NotNull_can_check_key(): void
    {
        $filter = Filters::notNull();
        self::assertTrue($filter->isAllowed(null, 'a', Check::KEY));
    }
    
    public function test_NotNull_can_check_both_key_and_value(): void
    {
        $filter = Filters::notNull();
        self::assertTrue($filter->isAllowed('5', '5', Check::BOTH));
        self::assertFalse($filter->isAllowed(null, '5', Check::BOTH));
        self::assertFalse($filter->isAllowed('5', null, Check::BOTH));
    }
    
    public function test_NotNull_can_check_any_key_or_value(): void
    {
        $filter = Filters::notNull();
        self::assertTrue($filter->isAllowed('5', null, Check::ANY));
        self::assertTrue($filter->isAllowed(null, '5', Check::ANY));
        self::assertFalse($filter->isAllowed(null, null, Check::ANY));
    }
    
    public function test_NotNull_throws_exception_on_invalid_mode(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        Filters::notNull()->isAllowed(1, 1, 0);
    }
    
    public function test_NotEmpty_can_check_key(): void
    {
        $filter = Filters::notEmpty();
        self::assertTrue($filter->isAllowed('', 'a', Check::KEY));
    }
    
    public function test_NotEmpty_can_check_both_key_and_value(): void
    {
        $filter = Filters::notEmpty();
        self::assertTrue($filter->isAllowed('5', '5', Check::BOTH));
        self::assertFalse($filter->isAllowed(0, '5', Check::BOTH));
        self::assertFalse($filter->isAllowed('5', false, Check::BOTH));
    }
    
    public function test_NotEmpty_can_check_any_key_or_value(): void
    {
        $filter = Filters::notEmpty();
        self::assertTrue($filter->isAllowed('5', false, Check::ANY));
        self::assertTrue($filter->isAllowed('0', '5', Check::ANY));
        self::assertFalse($filter->isAllowed(0, '0', Check::ANY));
    }
    
    public function test_NotEmpty_throws_exception_on_invalid_mode(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        Filters::notEmpty()->isAllowed(1, 1, 0);
    }
    
    public function test_IsString_throws_exception_on_invalid_mode(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        Filters::isString()->isAllowed(1, 1, 0);
    }
    
    public function test_OnlyIn_throws_exception_on_empty_required_values(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        Filters::onlyIn([]);
    }
    
    public function test_OnlyIn_throws_exception_on_invalid_mode_hashmap(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        Filters::onlyIn(['test'])->isAllowed('a', 'a', 0);
    }
    
    public function test_OnlyIn_throws_exception_on_invalid_mode_not_hashmap(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        Filters::onlyIn([false])->isAllowed('a', 'a', 0);
    }
    
    public function test_OnlyIn_can_compare_key_for_nonhashmap_values(): void
    {
        $filter = Filters::onlyIn([[1], [2], ['a']]);
        
        self::assertTrue($filter->isAllowed([5], ['a'], Check::KEY));
    }
    
    public function test_OnlyIn_can_compare_both_key_and_value_for_nonhashmap_values(): void
    {
        $filter = Filters::onlyIn([[1], ['a']]);
        
        self::assertTrue($filter->isAllowed([1], ['a'], Check::BOTH));
        self::assertFalse($filter->isAllowed([5], ['a'], Check::BOTH));
        self::assertFalse($filter->isAllowed([1], ['c'], Check::BOTH));
    }
    
    public function test_OnlyIn_can_compare_any_key_or_value_for_nonhashmap_values(): void
    {
        $filter = Filters::onlyIn([[1], ['a']]);
        
        self::assertTrue($filter->isAllowed([1], ['a'], Check::ANY));
        self::assertTrue($filter->isAllowed([5], ['a'], Check::ANY));
        self::assertTrue($filter->isAllowed([1], ['c'], Check::ANY));
        self::assertFalse($filter->isAllowed([5], ['c'], Check::ANY));
    }
    
    public function test_Length_can_compare_length_of_string(): void
    {
        $filter = Filters::length()->ge(4);
        self::assertTrue($filter->isAllowed('something', 1));
    }
    
    public function test_Length_throws_exception_for_invalid_argument(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $filter = Filters::length()->ge(4);
        $filter->isAllowed(15, 1);
    }
    
    public function test_Length_greaterThan(): void
    {
        $filter = Filters::length()->gt(5);
        
        self::assertFalse($filter->isAllowed('asdf', 1));
        self::assertFalse($filter->isAllowed('asdfg', 1));
        self::assertTrue($filter->isAllowed('asdfgh', 1));
    }
    
    public function test_Length_lessThan(): void
    {
        $filter = Filters::length()->lt(5);
        
        self::assertTrue($filter->isAllowed('asdf', 1));
        self::assertFalse($filter->isAllowed('asdfg', 1));
        self::assertFalse($filter->isAllowed('asdfgh', 1));
    }
    
    public function test_Length_lessThanOrEqual(): void
    {
        $filter = Filters::length()->le(5);
        
        self::assertTrue($filter->isAllowed('asdf', 1));
        self::assertTrue($filter->isAllowed('asdfg', 1));
        self::assertFalse($filter->isAllowed('asdfgh', 1));
    }
    
    public function test_Length_nonEqual(): void
    {
        $filter = Filters::length()->ne(5);
        
        self::assertTrue($filter->isAllowed('asdf', 1));
        self::assertFalse($filter->isAllowed('asdfg', 1));
        self::assertTrue($filter->isAllowed('asdfgh', 1));
    }
    
    public function test_Length_throws_exception_on_invalid_type(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        
        $filter = new Length(15, 'aaa');
        $filter->isAllowed('asdasd', 3);
    }
    
    public function test_Length_can_compare_key(): void
    {
        $filter = Filters::length()->lt(5);
        
        self::assertTrue($filter->isAllowed(15, 'asdf', Check::KEY));
        self::assertFalse($filter->isAllowed(15, 'asdfg', Check::KEY));
    }
    
    public function test_Length_can_compare_both_key_and_value(): void
    {
        $filter = Filters::length()->lt(5);
        
        self::assertTrue($filter->isAllowed([1,2,3,4], 'asdf', Check::BOTH));
        self::assertFalse($filter->isAllowed([1,2,3,4], 'asdfg', Check::BOTH));
        self::assertFalse($filter->isAllowed([1,2,3,4,5], 'asdf', Check::BOTH));
    }
    
    public function test_Length_can_compare_any_key_or_value(): void
    {
        $filter = Filters::length()->lt(5);
        
        self::assertTrue($filter->isAllowed([1,2,3,4], 'asdf', Check::ANY));
        self::assertTrue($filter->isAllowed([1,2,3,4], 'asdfg', Check::ANY));
        self::assertTrue($filter->isAllowed([1,2,3,4,5], 'asdf', Check::ANY));
        self::assertFalse($filter->isAllowed([1,2,3,4,5], 'asdfg', Check::ANY));
    }
    
    public function test_Length_throws_exception_on_invalid_mode(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        Filters::length()->ne(5)->isAllowed([1234], 'aaaa', 0);
    }
    
    public function test_FilterBy_throws_exception_on_invalid_param_field(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        Filters::filterBy(false, 'is_int');
    }
    
    public function test_FilterBy_throws_exception_when_tested_value_has_invalid_type(): void
    {
        $this->expectException(\LogicException::class);
        
        Filters::filterBy('id', 'is_int')->isAllowed(15, 1);
    }
    
    public function test_FilterBy_throws_exception_when_field_is_not_present_in_value(): void
    {
        $this->expectException(\RuntimeException::class);
        
        Filters::filterBy('id', 'is_int')->isAllowed(['name' => 'Joe'], 1);
    }
}