<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Filter\Adapter\PredicateAdapter;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Filter\IsBool;
use FiiSoft\Jackdaw\Filter\IsFloat;
use FiiSoft\Jackdaw\Filter\IsInt;
use FiiSoft\Jackdaw\Filter\IsNull;
use FiiSoft\Jackdaw\Filter\IsNumeric;
use FiiSoft\Jackdaw\Filter\IsString;
use FiiSoft\Jackdaw\Filter\Length;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Predicate\Predicates;
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
        $filter = Filters::same(5);
        
        self::assertTrue($filter->isAllowed(5, 5, Check::BOTH));
        self::assertFalse($filter->isAllowed(5, 1, Check::BOTH));
        self::assertFalse($filter->isAllowed(1, 5, Check::BOTH));
    }
    
    public function test_Equal_can_compare_any_value_or_key(): void
    {
        $filter = Filters::same(5);
        
        self::assertTrue($filter->isAllowed(5, 5, Check::ANY));
        self::assertTrue($filter->isAllowed(5, 1, Check::ANY));
        self::assertTrue($filter->isAllowed(1, 5, Check::ANY));
        self::assertFalse($filter->isAllowed(1, 1, Check::ANY));
    }
    
    public function test_Equal_throws_exception_on_invalid_mode(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $filter = Filters::same(5);
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
    
    public function test_OnlyWith_thros_exception_when_param_keys_is_invalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param keys');
        
        Filters::onlyWith(false);
    }
    
    public function test_OnlyWith_returns_false_for_each_unrecognizable_argument(): void
    {
        $filter = Filters::onlyWith('key');
        
        self::assertFalse($filter->isAllowed('aaa', 1));
    }
    
    public function test_OnlyWith_can_handle_ArrayAccess_argument(): void
    {
        $withKey = new \ArrayObject(['key' => 1]);
        $withoutKey = new \ArrayObject(['other' => 1]);
        
        $filter = Filters::onlyWith('key', true);
        
        self::assertTrue($filter->isAllowed($withKey, 'a'));
        self::assertFalse($filter->isAllowed($withoutKey, 'a'));
    }
    
    public function test_IsNull_all_variations(): void
    {
        $filter = Filters::isNull();
        
        self::assertFalse($filter->isAllowed(1, 1, Check::VALUE));
        self::assertFalse($filter->isAllowed(1, 1, Check::KEY));
        self::assertFalse($filter->isAllowed(1, 1, Check::BOTH));
        self::assertFalse($filter->isAllowed(1, 1, Check::ANY));
        
        self::assertTrue($filter->isAllowed(null, 1, Check::VALUE));
        self::assertFalse($filter->isAllowed(null, 1, Check::KEY));
        self::assertFalse($filter->isAllowed(null, 1, Check::BOTH));
        self::assertTrue($filter->isAllowed(null, 1, Check::ANY));
        
        self::assertFalse($filter->isAllowed(1, null, Check::VALUE));
        self::assertTrue($filter->isAllowed(1, null, Check::KEY));
        self::assertFalse($filter->isAllowed(1, null, Check::BOTH));
        self::assertTrue($filter->isAllowed(1, null, Check::ANY));
        
        self::assertTrue($filter->isAllowed(null, null, Check::VALUE));
        self::assertTrue($filter->isAllowed(null, null, Check::KEY));
        self::assertTrue($filter->isAllowed(null, null, Check::BOTH));
        self::assertTrue($filter->isAllowed(null, null, Check::ANY));
    }
    
    public function test_IsNull_throws_exception_when_mode_is_invalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param mode');
    
        Filters::isNull()->isAllowed(1, 1, 5);
    }
    
    public function test_IsFloat_all_variations(): void
    {
        $filter = Filters::isFloat();
        
        self::assertFalse($filter->isAllowed(1, 1, Check::VALUE));
        self::assertFalse($filter->isAllowed(1, 1, Check::KEY));
        self::assertFalse($filter->isAllowed(1, 1, Check::BOTH));
        self::assertFalse($filter->isAllowed(1, 1, Check::ANY));
        
        self::assertTrue($filter->isAllowed(1.0, 1, Check::VALUE));
        self::assertFalse($filter->isAllowed(1.0, 1, Check::KEY));
        self::assertFalse($filter->isAllowed(1.0, 1, Check::BOTH));
        self::assertTrue($filter->isAllowed(1.0, 1, Check::ANY));
        
        self::assertFalse($filter->isAllowed(1, 1.0, Check::VALUE));
        self::assertTrue($filter->isAllowed(1, 1.0, Check::KEY));
        self::assertFalse($filter->isAllowed(1, 1.0, Check::BOTH));
        self::assertTrue($filter->isAllowed(1, 1.0, Check::ANY));
        
        self::assertTrue($filter->isAllowed(1.0, 1.0, Check::VALUE));
        self::assertTrue($filter->isAllowed(1.0, 1.0, Check::KEY));
        self::assertTrue($filter->isAllowed(1.0, 1.0, Check::BOTH));
        self::assertTrue($filter->isAllowed(1.0, 1.0, Check::ANY));
    }
    
    public function test_IsFloat_throws_exception_when_mode_is_invalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param mode');
    
        Filters::isFloat()->isAllowed(1.0, 1.0, 5);
    }
    
    public function test_IsBool_all_variations(): void
    {
        $filter = Filters::isBool();
        
        self::assertFalse($filter->isAllowed(1, 1, Check::VALUE));
        self::assertFalse($filter->isAllowed(1, 1, Check::KEY));
        self::assertFalse($filter->isAllowed(1, 1, Check::BOTH));
        self::assertFalse($filter->isAllowed(1, 1, Check::ANY));
        
        self::assertTrue($filter->isAllowed(true, 1, Check::VALUE));
        self::assertFalse($filter->isAllowed(true, 1, Check::KEY));
        self::assertFalse($filter->isAllowed(true, 1, Check::BOTH));
        self::assertTrue($filter->isAllowed(true, 1, Check::ANY));
        
        self::assertFalse($filter->isAllowed(1, true, Check::VALUE));
        self::assertTrue($filter->isAllowed(1, true, Check::KEY));
        self::assertFalse($filter->isAllowed(1, true, Check::BOTH));
        self::assertTrue($filter->isAllowed(1, true, Check::ANY));
        
        self::assertTrue($filter->isAllowed(true, true, Check::VALUE));
        self::assertTrue($filter->isAllowed(true, true, Check::KEY));
        self::assertTrue($filter->isAllowed(true, true, Check::BOTH));
        self::assertTrue($filter->isAllowed(true, true, Check::ANY));
    }
    
    public function test_IsBool_throws_exception_when_mode_is_invalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param mode');
    
        Filters::isBool()->isAllowed(true, true, 5);
    }
    
    public function test_getAdapters_can_make_various_filter_adapters(): void
    {
        self::assertInstanceOf(IsInt::class, Filters::getAdapter('is_int'));
        self::assertInstanceOf(IsInt::class, Filters::getAdapter('\is_int'));
        
        self::assertInstanceOf(IsNumeric::class, Filters::getAdapter('is_numeric'));
        self::assertInstanceOf(IsNumeric::class, Filters::getAdapter('\is_numeric'));
        
        self::assertInstanceOf(IsString::class, Filters::getAdapter('is_string'));
        self::assertInstanceOf(IsString::class, Filters::getAdapter('\is_string'));
        
        self::assertInstanceOf(IsFloat::class, Filters::getAdapter('is_float'));
        self::assertInstanceOf(IsFloat::class, Filters::getAdapter('\is_float'));
        
        self::assertInstanceOf(IsNull::class, Filters::getAdapter('is_null'));
        self::assertInstanceOf(IsNull::class, Filters::getAdapter('\is_null'));
        
        self::assertInstanceOf(IsBool::class, Filters::getAdapter('is_bool'));
        self::assertInstanceOf(IsBool::class, Filters::getAdapter('\is_bool'));
        
        self::assertInstanceOf(PredicateAdapter::class, Filters::getAdapter(Predicates::inArray([1, 2])));
    }
    
    public function test_it_allows_to_use_any_Predicate_as_Filter(): void
    {
        $filter = Filters::getAdapter(Predicates::inArray([1, 2]));
        
        self::assertTrue($filter->isAllowed(1, 'a'));
        self::assertFalse($filter->isAllowed(3, 'a'));
    }
    
    public function test_NumbeFilter_Equal_can_compare_number_and_numeric_type(): void
    {
        $filter = Filters::number()->eq(5);
        
        self::assertTrue($filter->isAllowed(5, 5, Check::VALUE));
        self::assertTrue($filter->isAllowed(5, 5, Check::KEY));
        self::assertTrue($filter->isAllowed(5, 5, Check::BOTH));
        self::assertTrue($filter->isAllowed(5, 5, Check::ANY));
        
        self::assertTrue($filter->isAllowed(5, 1, Check::VALUE));
        self::assertFalse($filter->isAllowed(5, 1, Check::KEY));
        self::assertFalse($filter->isAllowed(5, 1, Check::BOTH));
        self::assertTrue($filter->isAllowed(5, 1, Check::ANY));
        
        self::assertFalse($filter->isAllowed(1, 5, Check::VALUE));
        self::assertTrue($filter->isAllowed(1, 5, Check::KEY));
        self::assertFalse($filter->isAllowed(1, 5, Check::BOTH));
        self::assertTrue($filter->isAllowed(1, 5, Check::ANY));
        
        self::assertFalse($filter->isAllowed(1, 1, Check::VALUE));
        self::assertFalse($filter->isAllowed(1, 1, Check::KEY));
        self::assertFalse($filter->isAllowed(1, 1, Check::BOTH));
        self::assertFalse($filter->isAllowed(1, 1, Check::ANY));
        
        self::assertTrue($filter->isAllowed('5', '5', Check::VALUE));
        self::assertTrue($filter->isAllowed('5', '5', Check::KEY));
        self::assertTrue($filter->isAllowed('5', '5', Check::BOTH));
        self::assertTrue($filter->isAllowed('5', '5', Check::ANY));
        
        self::assertTrue($filter->isAllowed('5', 1, Check::VALUE));
        self::assertFalse($filter->isAllowed('5', 1, Check::KEY));
        self::assertFalse($filter->isAllowed('5', 1, Check::BOTH));
        self::assertTrue($filter->isAllowed('5', 1, Check::ANY));
        
        self::assertFalse($filter->isAllowed(1, '5', Check::VALUE));
        self::assertTrue($filter->isAllowed(1, '5', Check::KEY));
        self::assertFalse($filter->isAllowed(1, '5', Check::BOTH));
        self::assertTrue($filter->isAllowed(1, '5', Check::ANY));
    }
    
    public function test_NumberFilter_Equal_throws_exception_when_tested_value_is_not_a_number(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot compare value which is not a number');
        
        Filters::number()->eq(5)->isAllowed(['wrong arg'], 1);
    }
    
    public function test_NumbeFilter_NotEqual_can_compare_number_and_numeric_type(): void
    {
        $filter = Filters::number()->ne(1);
        
        self::assertTrue($filter->isAllowed(5, 5, Check::VALUE));
        self::assertTrue($filter->isAllowed(5, 5, Check::KEY));
        self::assertTrue($filter->isAllowed(5, 5, Check::BOTH));
        self::assertTrue($filter->isAllowed(5, 5, Check::ANY));
        
        self::assertTrue($filter->isAllowed(5, 1, Check::VALUE));
        self::assertFalse($filter->isAllowed(5, 1, Check::KEY));
        self::assertFalse($filter->isAllowed(5, 1, Check::BOTH));
        self::assertTrue($filter->isAllowed(5, 1, Check::ANY));
        
        self::assertFalse($filter->isAllowed(1, 5, Check::VALUE));
        self::assertTrue($filter->isAllowed(1, 5, Check::KEY));
        self::assertFalse($filter->isAllowed(1, 5, Check::BOTH));
        self::assertTrue($filter->isAllowed(1, 5, Check::ANY));
        
        self::assertFalse($filter->isAllowed(1, 1, Check::VALUE));
        self::assertFalse($filter->isAllowed(1, 1, Check::KEY));
        self::assertFalse($filter->isAllowed(1, 1, Check::BOTH));
        self::assertFalse($filter->isAllowed(1, 1, Check::ANY));
        
        self::assertTrue($filter->isAllowed('5', '5', Check::VALUE));
        self::assertTrue($filter->isAllowed('5', '5', Check::KEY));
        self::assertTrue($filter->isAllowed('5', '5', Check::BOTH));
        self::assertTrue($filter->isAllowed('5', '5', Check::ANY));
        
        self::assertTrue($filter->isAllowed('5', 1, Check::VALUE));
        self::assertFalse($filter->isAllowed('5', 1, Check::KEY));
        self::assertFalse($filter->isAllowed('5', 1, Check::BOTH));
        self::assertTrue($filter->isAllowed('5', 1, Check::ANY));
        
        self::assertFalse($filter->isAllowed(1, '5', Check::VALUE));
        self::assertTrue($filter->isAllowed(1, '5', Check::KEY));
        self::assertFalse($filter->isAllowed(1, '5', Check::BOTH));
        self::assertTrue($filter->isAllowed(1, '5', Check::ANY));
    }
    
    public function test_NumberFilter_NotEqual_throws_exception_when_tested_value_is_not_a_number(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot compare value which is not a number');
        
        Filters::number()->ne(5)->isAllowed(['wrong arg'], 1);
    }
    
    public function test_NumberFilter_allows_to_test_numbers_in_various_ways(): void
    {
        $equal = Filters::number()->eq(5);
        self::assertTrue($equal->isAllowed(5, 'a'));
        self::assertFalse($equal->isAllowed(1, 'a'));
        
        $notEqual = Filters::number()->ne(5);
        self::assertFalse($notEqual->isAllowed(5, 'a'));
        self::assertTrue($notEqual->isAllowed(1, 'a'));
        
        $greater = Filters::number()->gt(5);
        self::assertTrue($greater->isAllowed(6, 'a'));
        self::assertFalse($greater->isAllowed(5, 'a'));
        
        $less = Filters::number()->lt(5);
        self::assertTrue($less->isAllowed(4, 'a'));
        self::assertFalse($less->isAllowed(5, 'a'));
        
        $greaterEqual = Filters::number()->ge(5);
        self::assertTrue($greaterEqual->isAllowed(6, 'a'));
        self::assertTrue($greaterEqual->isAllowed(5, 'a'));
        
        $lessEqual = Filters::number()->le(5);
        self::assertTrue($lessEqual->isAllowed(4, 'a'));
        self::assertTrue($lessEqual->isAllowed(5, 'a'));
    }
    
    public function test_Contains_without_ignore_case(): void
    {
        $filter = Filters::contains('foo');
        
        self::assertTrue($filter->isAllowed('contains foo within', 'key', Check::VALUE));
        self::assertFalse($filter->isAllowed('contains foo within', 'key', Check::KEY));
        self::assertFalse($filter->isAllowed('contains foo within', 'key', Check::BOTH));
        self::assertTrue($filter->isAllowed('contains foo within', 'key', Check::ANY));
        
        self::assertFalse($filter->isAllowed('contains something else', 'key with foo inside', Check::VALUE));
        self::assertTrue($filter->isAllowed('contains something else', 'key with foo inside', Check::KEY));
        self::assertFalse($filter->isAllowed('contains something else', 'key with foo inside', Check::BOTH));
        self::assertTrue($filter->isAllowed('contains something else', 'key with foo inside', Check::ANY));
        
        self::assertTrue($filter->isAllowed('contains foo within', 'key with foo inside', Check::VALUE));
        self::assertTrue($filter->isAllowed('contains foo within', 'key with foo inside', Check::KEY));
        self::assertTrue($filter->isAllowed('contains foo within', 'key with foo inside', Check::BOTH));
        self::assertTrue($filter->isAllowed('contains foo within', 'key with foo inside', Check::ANY));
        
        self::assertFalse($filter->isAllowed('contains something else', 'key', Check::VALUE));
        self::assertFalse($filter->isAllowed('contains something else', 'key', Check::KEY));
        self::assertFalse($filter->isAllowed('contains something else', 'key', Check::BOTH));
        self::assertFalse($filter->isAllowed('contains something else', 'key', Check::ANY));
        
        self::assertFalse($filter->isAllowed('contains Foo within', 'key with Foo inside', Check::VALUE));
        self::assertFalse($filter->isAllowed('contains Foo within', 'key with Foo inside', Check::KEY));
        self::assertFalse($filter->isAllowed('contains Foo within', 'key with Foo inside', Check::BOTH));
        self::assertFalse($filter->isAllowed('contains Foo within', 'key with Foo inside', Check::ANY));
    }
    
    public function test_Contains_with_ignore_case(): void
    {
        $filter = Filters::contains('foo', true);
        
        self::assertTrue($filter->isAllowed('contains Foo within', 'key', Check::VALUE));
        self::assertFalse($filter->isAllowed('contains Foo within', 'key', Check::KEY));
        self::assertFalse($filter->isAllowed('contains Foo within', 'key', Check::BOTH));
        self::assertTrue($filter->isAllowed('contains Foo within', 'key', Check::ANY));
        
        self::assertFalse($filter->isAllowed('contains something else', 'key with Foo inside', Check::VALUE));
        self::assertTrue($filter->isAllowed('contains something else', 'key with Foo inside', Check::KEY));
        self::assertFalse($filter->isAllowed('contains something else', 'key with Foo inside', Check::BOTH));
        self::assertTrue($filter->isAllowed('contains something else', 'key with Foo inside', Check::ANY));
        
        self::assertTrue($filter->isAllowed('contains Foo within', 'key with Foo inside', Check::VALUE));
        self::assertTrue($filter->isAllowed('contains Foo within', 'key with Foo inside', Check::KEY));
        self::assertTrue($filter->isAllowed('contains Foo within', 'key with Foo inside', Check::BOTH));
        self::assertTrue($filter->isAllowed('contains Foo within', 'key with Foo inside', Check::ANY));
        
        self::assertFalse($filter->isAllowed('contains something else', 'key', Check::VALUE));
        self::assertFalse($filter->isAllowed('contains something else', 'key', Check::KEY));
        self::assertFalse($filter->isAllowed('contains something else', 'key', Check::BOTH));
        self::assertFalse($filter->isAllowed('contains something else', 'key', Check::ANY));
    }
    
    public function test_StartsWith_without_ignore_case(): void
    {
        $filter = Filters::startsWith('foo');
        
        self::assertTrue($filter->isAllowed('foo value', 'key', Check::VALUE));
        self::assertFalse($filter->isAllowed('foo value', 'key', Check::KEY));
        self::assertFalse($filter->isAllowed('foo value', 'key', Check::BOTH));
        self::assertTrue($filter->isAllowed('foo value', 'key', Check::ANY));
        
        self::assertFalse($filter->isAllowed('other value', 'foo key', Check::VALUE));
        self::assertTrue($filter->isAllowed('other value', 'foo key', Check::KEY));
        self::assertFalse($filter->isAllowed('other value', 'foo key', Check::BOTH));
        self::assertTrue($filter->isAllowed('other value', 'foo key', Check::ANY));
        
        self::assertTrue($filter->isAllowed('foo value', 'foo key', Check::VALUE));
        self::assertTrue($filter->isAllowed('foo value', 'foo key', Check::KEY));
        self::assertTrue($filter->isAllowed('foo value', 'foo key', Check::BOTH));
        self::assertTrue($filter->isAllowed('foo value', 'foo key', Check::ANY));
        
        self::assertFalse($filter->isAllowed('other value', 'key', Check::VALUE));
        self::assertFalse($filter->isAllowed('other value', 'key', Check::KEY));
        self::assertFalse($filter->isAllowed('other value', 'key', Check::BOTH));
        self::assertFalse($filter->isAllowed('other value', 'key', Check::ANY));
        
        self::assertFalse($filter->isAllowed('Foo value', 'Foo key', Check::VALUE));
        self::assertFalse($filter->isAllowed('Foo value', 'Foo key', Check::KEY));
        self::assertFalse($filter->isAllowed('Foo value', 'Foo key', Check::BOTH));
        self::assertFalse($filter->isAllowed('Foo value', 'Foo key', Check::ANY));
    }
    
    public function test_StartsWith_with_ignore_case(): void
    {
        $filter = Filters::startsWith('foo', true);
        
        self::assertTrue($filter->isAllowed('Foo value', 'key', Check::VALUE));
        self::assertFalse($filter->isAllowed('Foo value', 'key', Check::KEY));
        self::assertFalse($filter->isAllowed('Foo value', 'key', Check::BOTH));
        self::assertTrue($filter->isAllowed('Foo value', 'key', Check::ANY));
        
        self::assertFalse($filter->isAllowed('other value', 'Foo key', Check::VALUE));
        self::assertTrue($filter->isAllowed('other value', 'Foo key', Check::KEY));
        self::assertFalse($filter->isAllowed('other value', 'Foo key', Check::BOTH));
        self::assertTrue($filter->isAllowed('other value', 'Foo key', Check::ANY));
        
        self::assertTrue($filter->isAllowed('Foo value', 'Foo key', Check::VALUE));
        self::assertTrue($filter->isAllowed('Foo value', 'Foo key', Check::KEY));
        self::assertTrue($filter->isAllowed('Foo value', 'Foo key', Check::BOTH));
        self::assertTrue($filter->isAllowed('Foo value', 'Foo key', Check::ANY));
        
        self::assertFalse($filter->isAllowed('other value', 'key', Check::VALUE));
        self::assertFalse($filter->isAllowed('other value', 'key', Check::KEY));
        self::assertFalse($filter->isAllowed('other value', 'key', Check::BOTH));
        self::assertFalse($filter->isAllowed('other value', 'key', Check::ANY));
    }
    
    public function test_EndsWith_without_ignore_case(): void
    {
        $filter = Filters::endsWith('foo');
        
        self::assertTrue($filter->isAllowed('value foo', 'key', Check::VALUE));
        self::assertFalse($filter->isAllowed('value foo', 'key', Check::KEY));
        self::assertFalse($filter->isAllowed('value foo', 'key', Check::BOTH));
        self::assertTrue($filter->isAllowed('value foo', 'key', Check::ANY));
        
        self::assertFalse($filter->isAllowed('value other', 'key foo', Check::VALUE));
        self::assertTrue($filter->isAllowed('value other', 'key foo', Check::KEY));
        self::assertFalse($filter->isAllowed('value other', 'key foo', Check::BOTH));
        self::assertTrue($filter->isAllowed('value other', 'key foo', Check::ANY));
        
        self::assertTrue($filter->isAllowed('value foo', 'key foo', Check::VALUE));
        self::assertTrue($filter->isAllowed('value foo', 'key foo', Check::KEY));
        self::assertTrue($filter->isAllowed('value foo', 'key foo', Check::BOTH));
        self::assertTrue($filter->isAllowed('value foo', 'key foo', Check::ANY));
        
        self::assertFalse($filter->isAllowed('value other', 'key', Check::VALUE));
        self::assertFalse($filter->isAllowed('value other', 'key', Check::KEY));
        self::assertFalse($filter->isAllowed('value other', 'key', Check::BOTH));
        self::assertFalse($filter->isAllowed('value other', 'key', Check::ANY));
        
        self::assertFalse($filter->isAllowed('value Foo', 'key Foo', Check::VALUE));
        self::assertFalse($filter->isAllowed('value Foo', 'key Foo', Check::KEY));
        self::assertFalse($filter->isAllowed('value Foo', 'key Foo', Check::BOTH));
        self::assertFalse($filter->isAllowed('value Foo', 'key Foo', Check::ANY));
    }
    
    public function test_EndsWith_with_ignore_case(): void
    {
        $filter = Filters::endsWith('foo', true);
        
        self::assertTrue($filter->isAllowed('value Foo', 'key', Check::VALUE));
        self::assertFalse($filter->isAllowed('value Foo', 'key', Check::KEY));
        self::assertFalse($filter->isAllowed('value Foo', 'key', Check::BOTH));
        self::assertTrue($filter->isAllowed('value Foo', 'key', Check::ANY));
        
        self::assertFalse($filter->isAllowed('value other', 'key Foo', Check::VALUE));
        self::assertTrue($filter->isAllowed('value other', 'key Foo', Check::KEY));
        self::assertFalse($filter->isAllowed('value other', 'key Foo', Check::BOTH));
        self::assertTrue($filter->isAllowed('value other', 'key Foo', Check::ANY));
        
        self::assertTrue($filter->isAllowed('value Foo', 'key Foo', Check::VALUE));
        self::assertTrue($filter->isAllowed('value Foo', 'key Foo', Check::KEY));
        self::assertTrue($filter->isAllowed('value Foo', 'key Foo', Check::BOTH));
        self::assertTrue($filter->isAllowed('value Foo', 'key Foo', Check::ANY));
        
        self::assertFalse($filter->isAllowed('value other', 'key', Check::VALUE));
        self::assertFalse($filter->isAllowed('value other', 'key', Check::KEY));
        self::assertFalse($filter->isAllowed('value other', 'key', Check::BOTH));
        self::assertFalse($filter->isAllowed('value other', 'key', Check::ANY));
    }
    
    public function test_every_StringFilter_throws_exception_when_param_mode_is_invalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param mode');
        
        Filters::contains('foo')->isAllowed('foo', 'key', 5);
    }
    
    public function test_string_filters(): void
    {
        self::assertFalse(Filters::string()->contains('very long string')->isAllowed('long', 1));
        self::assertFalse(Filters::string()->startsWith('very long string')->isAllowed('very long', 1));
        self::assertFalse(Filters::string()->endsWith('very long string')->isAllowed('long string', 1));
        
        self::assertTrue(Filters::string()->contains('long string')->isAllowed('long string', 1));
        self::assertTrue(Filters::string()->startsWith('long string')->isAllowed('long string', 1));
        self::assertTrue(Filters::string()->endsWith('long string')->isAllowed('long string', 1));
    }
    
    public function test_IsEven(): void
    {
        $filter = Filters::number()->isEven();
    
        self::assertTrue($filter->isAllowed(2, 1, Check::VALUE));
        self::assertFalse($filter->isAllowed(2, 1, Check::KEY));
        self::assertFalse($filter->isAllowed(2, 1, Check::BOTH));
        self::assertTrue($filter->isAllowed(2, 1, Check::ANY));
    
        self::assertFalse($filter->isAllowed(1, 2, Check::VALUE));
        self::assertTrue($filter->isAllowed(1, 2, Check::KEY));
        self::assertFalse($filter->isAllowed(1, 2, Check::BOTH));
        self::assertTrue($filter->isAllowed(1, 2, Check::ANY));
    
        self::assertTrue($filter->isAllowed(2, 2, Check::VALUE));
        self::assertTrue($filter->isAllowed(2, 2, Check::KEY));
        self::assertTrue($filter->isAllowed(2, 2, Check::BOTH));
        self::assertTrue($filter->isAllowed(2, 2, Check::ANY));
    
        self::assertFalse($filter->isAllowed(1, 1, Check::VALUE));
        self::assertFalse($filter->isAllowed(1, 1, Check::KEY));
        self::assertFalse($filter->isAllowed(1, 1, Check::BOTH));
        self::assertFalse($filter->isAllowed(1, 1, Check::ANY));
    }
    
    public function test_IsEven_throws_exception_when_param_mode_is_invalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param mode');
        
        Filters::number()->isEven()->isAllowed('foo', 'key', 5);
    }
    
    public function test_IsOdd(): void
    {
        $filter = Filters::number()->isOdd();
    
        self::assertTrue($filter->isAllowed(1, 2, Check::VALUE));
        self::assertFalse($filter->isAllowed(1, 2, Check::KEY));
        self::assertFalse($filter->isAllowed(1, 2, Check::BOTH));
        self::assertTrue($filter->isAllowed(1, 2, Check::ANY));
    
        self::assertFalse($filter->isAllowed(2, 1, Check::VALUE));
        self::assertTrue($filter->isAllowed(2, 1, Check::KEY));
        self::assertFalse($filter->isAllowed(2, 1, Check::BOTH));
        self::assertTrue($filter->isAllowed(2, 1, Check::ANY));
    
        self::assertTrue($filter->isAllowed(1, 1, Check::VALUE));
        self::assertTrue($filter->isAllowed(1, 1, Check::KEY));
        self::assertTrue($filter->isAllowed(1, 1, Check::BOTH));
        self::assertTrue($filter->isAllowed(1, 1, Check::ANY));
    
        self::assertFalse($filter->isAllowed(2, 2, Check::VALUE));
        self::assertFalse($filter->isAllowed(2, 2, Check::KEY));
        self::assertFalse($filter->isAllowed(2, 2, Check::BOTH));
        self::assertFalse($filter->isAllowed(2, 2, Check::ANY));
    }
    
    public function test_IsOdd_throws_exception_when_param_mode_is_invalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param mode');
        
        Filters::number()->isOdd()->isAllowed('foo', 'key', 5);
    }
    
    public function test_FilterAND_throws_exception_when_list_of_filters_is_empty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Param filters cannot be empty');
        
        Filters::AND();
    }
    
    public function test_FilterOR_throws_exception_when_list_of_filters_is_empty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Param filters cannot be empty');
        
        Filters::OR();
    }
    
    public function test_FilterAND(): void
    {
        $filter = Filters::AND(Filters::greaterOrEqual(5), Filters::lessOrEqual(10));
        
        self::assertTrue($filter->isAllowed(8, 7));
        self::assertFalse($filter->isAllowed(11, 7));
        self::assertFalse($filter->isAllowed(4, 7));
        
        self::assertTrue($filter->isAllowed(7, 8, Check::KEY));
        self::assertFalse($filter->isAllowed(7, 11, Check::KEY));
        self::assertFalse($filter->isAllowed(7, 4, Check::KEY));
        
        self::assertTrue($filter->isAllowed(5, 8, Check::BOTH));
        self::assertFalse($filter->isAllowed(7, 11, Check::BOTH));
        self::assertFalse($filter->isAllowed(9, 4, Check::BOTH));
        self::assertFalse($filter->isAllowed(12, 4, Check::BOTH));
        self::assertFalse($filter->isAllowed(4, 12, Check::BOTH));
        
        self::assertTrue($filter->isAllowed(5, 8, Check::ANY));
        self::assertTrue($filter->isAllowed(7, 11, Check::ANY));
        self::assertTrue($filter->isAllowed(4, 9, Check::ANY));
        self::assertFalse($filter->isAllowed(11, 4, Check::ANY));
        self::assertFalse($filter->isAllowed(4, 11, Check::ANY));
    }
    
    public function test_FilterOR(): void
    {
        $filter = Filters::OR(Filters::lessOrEqual(5), Filters::greaterOrEqual(10));
        
        self::assertFalse($filter->isAllowed(8, 7));
        self::assertTrue($filter->isAllowed(11, 7));
        self::assertTrue($filter->isAllowed(4, 7));
        
        self::assertFalse($filter->isAllowed(7, 8, Check::KEY));
        self::assertTrue($filter->isAllowed(7, 11, Check::KEY));
        self::assertTrue($filter->isAllowed(7, 4, Check::KEY));
        
        self::assertFalse($filter->isAllowed(5, 8, Check::BOTH));
        self::assertFalse($filter->isAllowed(7, 11, Check::BOTH));
        self::assertFalse($filter->isAllowed(9, 4, Check::BOTH));
        self::assertTrue($filter->isAllowed(12, 4, Check::BOTH));
        self::assertTrue($filter->isAllowed(4, 12, Check::BOTH));
        
        self::assertFalse($filter->isAllowed(7, 8, Check::ANY));
        self::assertTrue($filter->isAllowed(5, 8, Check::ANY));
        self::assertTrue($filter->isAllowed(7, 11, Check::ANY));
        self::assertTrue($filter->isAllowed(4, 9, Check::ANY));
        self::assertTrue($filter->isAllowed(11, 4, Check::ANY));
        self::assertTrue($filter->isAllowed(4, 11, Check::ANY));
    }
    
    public function test_FilterNOT(): void
    {
        $filter = Filters::NOT(Filters::greaterThan(5));
        
        self::assertTrue($filter->isAllowed(5, 0));
        self::assertFalse($filter->isAllowed(6, 0));
        
        self::assertTrue($filter->isAllowed(0, 5, Check::KEY));
        self::assertFalse($filter->isAllowed(0, 6, Check::KEY));
        
        self::assertTrue($filter->isAllowed(7, 5, Check::BOTH));
        self::assertTrue($filter->isAllowed(0, 5, Check::BOTH));
        self::assertTrue($filter->isAllowed(7, 0, Check::BOTH));
        self::assertTrue($filter->isAllowed(0, 6, Check::BOTH));
        self::assertFalse($filter->isAllowed(8, 8, Check::BOTH));
        
        self::assertFalse($filter->isAllowed(7, 5, Check::ANY));
        self::assertFalse($filter->isAllowed(5, 7, Check::ANY));
        self::assertFalse($filter->isAllowed(8, 7, Check::ANY));
        self::assertTrue($filter->isAllowed(4, 3, Check::ANY));
    }
    
    public function test_FilterXOR(): void
    {
        $filter = Filters::XOR(Filters::greaterOrEqual(5), Filters::lessOrEqual(10));
        
        self::assertTrue($filter->isAllowed(12, 0));
        self::assertTrue($filter->isAllowed(3, 0));
        self::assertFalse($filter->isAllowed(7, 0));
        
        self::assertTrue($filter->isAllowed(0, 12, Check::KEY));
        self::assertTrue($filter->isAllowed(0, 3, Check::KEY));
        self::assertFalse($filter->isAllowed(0, 7, Check::KEY));
        
        self::assertTrue($filter->isAllowed(0, 12, Check::BOTH));
        self::assertTrue($filter->isAllowed(12, 0, Check::BOTH));
        self::assertTrue($filter->isAllowed(0, 3, Check::BOTH));
        self::assertTrue($filter->isAllowed(3, 0, Check::BOTH));
        self::assertFalse($filter->isAllowed(0, 7, Check::BOTH));
        self::assertFalse($filter->isAllowed(7, 0, Check::BOTH));
        self::assertFalse($filter->isAllowed(8, 7, Check::BOTH));
        self::assertFalse($filter->isAllowed(7, 8, Check::BOTH));
    }
    
    public function test_Between(): void
    {
        $filter = Filters::number()->between(5, 10);
        
        self::assertTrue($filter->isAllowed(8, 7));
        self::assertFalse($filter->isAllowed(11, 7));
        self::assertFalse($filter->isAllowed(4, 7));
    
        self::assertTrue($filter->isAllowed(7, 8, Check::KEY));
        self::assertFalse($filter->isAllowed(7, 11, Check::KEY));
        self::assertFalse($filter->isAllowed(7, 4, Check::KEY));
    
        self::assertTrue($filter->isAllowed(5, 8, Check::BOTH));
        self::assertFalse($filter->isAllowed(7, 11, Check::BOTH));
        self::assertFalse($filter->isAllowed(9, 4, Check::BOTH));
        self::assertFalse($filter->isAllowed(12, 4, Check::BOTH));
        self::assertFalse($filter->isAllowed(4, 12, Check::BOTH));
    
        self::assertTrue($filter->isAllowed(5, 8, Check::ANY));
        self::assertTrue($filter->isAllowed(7, 11, Check::ANY));
        self::assertTrue($filter->isAllowed(4, 9, Check::ANY));
        self::assertFalse($filter->isAllowed(11, 4, Check::ANY));
        self::assertFalse($filter->isAllowed(4, 11, Check::ANY));
    }
    
    public function test_Between_throws_exception_when_param_lower_is_invalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param lower');
        
        Filters::number()->between('a', 4);
    }
    
    public function test_Between_throws_exception_when_param_higher_is_invalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param higher');
        
        Filters::number()->between(4, 'a');
    }
    
    public function test_Between_throws_exception_when_param_lower_is_greater_then_higher(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Lower number is greater from higher number');
        
        Filters::number()->between(4, 2);
    }
    
    public function test_Between_throws_exception_when_param_mode_is_invalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param mode');
        
        Filters::number()->between(2, 2)->isAllowed(5, 2, 0);
    }
    
    public function test_Between_allows_to_compare_numeric_values_as_well(): void
    {
        $filter = Filters::number()->between(1, 10);
        
        self::assertTrue($filter->isAllowed('5', '12'));
        self::assertFalse($filter->isAllowed('5', '12', Check::KEY));
    }
    
    public function test_Between_throws_exception_when_value_to_compare_is_not_a_number(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot compare value which is not a number');
        
        Filters::number()->between(1, 3)->isAllowed('this is not a number', 1);
    }
}