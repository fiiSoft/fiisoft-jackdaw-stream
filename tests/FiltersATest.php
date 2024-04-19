<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Filter\CheckType\IsBool;
use FiiSoft\Jackdaw\Filter\CheckType\IsFloat;
use FiiSoft\Jackdaw\Filter\CheckType\IsInt;
use FiiSoft\Jackdaw\Filter\CheckType\IsNull;
use FiiSoft\Jackdaw\Filter\CheckType\IsNumeric;
use FiiSoft\Jackdaw\Filter\CheckType\IsString;
use FiiSoft\Jackdaw\Filter\Exception\FilterExceptionFactory;
use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Filter\IdleFilter;
use FiiSoft\Jackdaw\Filter\Number\Between;
use FiiSoft\Jackdaw\Filter\Number\Equal;
use FiiSoft\Jackdaw\Filter\Number\GreaterOrEqual;
use FiiSoft\Jackdaw\Filter\Number\GreaterThan;
use FiiSoft\Jackdaw\Filter\Number\Inside;
use FiiSoft\Jackdaw\Filter\Number\IsEven;
use FiiSoft\Jackdaw\Filter\Number\IsOdd;
use FiiSoft\Jackdaw\Filter\Number\LessOrEqual;
use FiiSoft\Jackdaw\Filter\Number\LessThan;
use FiiSoft\Jackdaw\Filter\Number\NotEqual;
use FiiSoft\Jackdaw\Filter\Number\NotInside;
use FiiSoft\Jackdaw\Filter\Number\Outside;
use FiiSoft\Jackdaw\Filter\OnlyIn\OnlyIn;
use FiiSoft\Jackdaw\Filter\Size\Count\CountFilter;
use FiiSoft\Jackdaw\Filter\Size\Length\LengthFilter;
use FiiSoft\Jackdaw\Filter\String\Contains;
use FiiSoft\Jackdaw\Filter\String\EndsWith;
use FiiSoft\Jackdaw\Filter\String\InSet;
use FiiSoft\Jackdaw\Filter\String\NotContains;
use FiiSoft\Jackdaw\Filter\String\NotEndsWith;
use FiiSoft\Jackdaw\Filter\String\NotInSet;
use FiiSoft\Jackdaw\Filter\String\NotStartsWith;
use FiiSoft\Jackdaw\Filter\String\StartsWith;
use FiiSoft\Jackdaw\Filter\String\StrIs;
use FiiSoft\Jackdaw\Filter\String\StrIsNot;
use FiiSoft\Jackdaw\Filter\Time\Compare\IdleTimeComp;
use FiiSoft\Jackdaw\Filter\Time\Compare\Point\Is;
use FiiSoft\Jackdaw\Filter\Time\Day;
use FiiSoft\Jackdaw\Filter\Time\TimeFilter;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Stream;
use PHPUnit\Framework\TestCase;

final class FiltersATest extends TestCase
{
    public function test_getAdapter_throws_exception_on_invalid_argument(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('filter'));
        
        Filters::getAdapter(new \stdClass());
    }
    
    /**
     * @dataProvider getDataForTestSomeNumberFiltersThrowsExceptionOnInvalidParam
     */
    public function test_some_number_filters_throws_exception_on_invalid_param(callable $factory): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('value'));
        
        $factory('15');
    }
    
    public static function getDataForTestSomeNumberFiltersThrowsExceptionOnInvalidParam(): iterable
    {
        yield 'Equal' => [static fn($value): Filter => Filters::number()->eq($value)];
        yield 'GreaterOrEqual' => [static fn($value): Filter => Filters::number()->ge($value)];
        yield 'GreaterThan' => [static fn($value): Filter => Filters::number()->gt($value)];
        yield 'LessOrEqual' => [static fn($value): Filter => Filters::number()->le($value)];
        yield 'LessThan' => [static fn($value): Filter => Filters::number()->lt($value)];
        yield 'NotEqual' => [static fn($value): Filter => Filters::number()->ne($value)];
    }
    
    public function test_GenericFilter_can_call_callable_without_arguments(): void
    {
        self::assertTrue(Filters::getAdapter(static fn(): bool => true)->isAllowed(1, 1));
        self::assertFalse(Filters::getAdapter(static fn(): bool => false)->isAllowed(1, 1));
    }
    
    public function test_GenericFilter_can_call_callable_with_two_arguments(): void
    {
        $value = null;
        $key = null;
        
        $filter = Filters::getAdapter(static function ($_value, $_key) use (&$value, &$key): bool {
            $value = $_value;
            $key = $_key;
            return true;
        });
        
        self::assertTrue($filter->isAllowed(10, 5));
        
        self::assertSame(5, $key);
        self::assertSame(10, $value);
    }
    
    public function test_GenericFilter_throws_exception_when_callable_has_unsupported_number_of_arguments(): void
    {
        $this->expectExceptionObject(FilterExceptionFactory::invalidParamFilter(4));
        
        $filter = Filters::getAdapter(static fn($a, $b, $c, $d): bool => true);
        $filter->isAllowed(1, 1);
    }
    
    public function test_GenericFilter_can_compare_key(): void
    {
        $filter = Filters::getAdapter(static fn($key): bool => $key === 'a');
        
        self::assertFalse($filter->isAllowed(15, 'a'));
        self::assertTrue($filter->inMode(Check::KEY)->isAllowed(15, 'a'));
    }
    
    public function test_GenericFilter_can_compare_value(): void
    {
        $filter = Filters::getAdapter(static fn($val): bool => $val === 15);
        
        self::assertTrue($filter->isAllowed(15, 'a'));
        self::assertFalse($filter->inMode(Check::KEY)->isAllowed(15, 'a'));
    }
    
    public function test_GenericFilter_can_compare_both_value_and_key(): void
    {
        $filter = Filters::getAdapter(static fn($val): bool => $val === 'a', Check::BOTH);
        
        self::assertTrue($filter->isAllowed('a', 'a'));
        self::assertFalse($filter->isAllowed(15, 'a'));
        self::assertFalse($filter->isAllowed('a', 15));
    }
    
    public function test_GenericFilter_can_compare_any_value_or_key(): void
    {
        $filter = Filters::getAdapter(static fn($val): bool => $val === 'a', Check::ANY);
        
        self::assertTrue($filter->isAllowed('a', 'a'));
        self::assertTrue($filter->isAllowed(15, 'a'));
        self::assertTrue($filter->isAllowed('a', 15));
        self::assertFalse($filter->isAllowed(15, 15));
    }
    
    public function test_GenericFilter_throws_exception_on_invalid_mode(): void
    {
        $this->expectExceptionObject(Check::invalidModeException(0));
        
        Filters::getAdapter(static fn($val): bool => $val === 'a', 0);
    }
    
    public function test_IsInt_throws_exception_on_invalid_mode(): void
    {
        $this->expectExceptionObject(Check::invalidModeException(0));
        
        Filters::isInt(0);
    }
    
    public function test_IsNumeric_throws_exception_on_invalid_mode(): void
    {
        $this->expectExceptionObject(Check::invalidModeException(0));
        
        Filters::isNumeric(0);
    }
    
    public function test_NotNull_throws_exception_on_invalid_mode(): void
    {
        $this->expectExceptionObject(Check::invalidModeException(0));
        
        Filters::notNull(0);
    }
    
    public function test_NotEmpty_throws_exception_on_invalid_mode(): void
    {
        $this->expectExceptionObject(Check::invalidModeException(0));
        
        Filters::notEmpty(0);
    }
    
    public function test_IsString_throws_exception_on_invalid_mode(): void
    {
        $this->expectExceptionObject(Check::invalidModeException(0));
        
        Filters::isString(0);
    }
    
    public function test_OnlyIn_throws_exception_on_invalid_mode_hashmap(): void
    {
        $this->expectExceptionObject(Check::invalidModeException(0));
        
        Filters::onlyIn(['test'], 0);
    }
    
    public function test_OnlyIn_throws_exception_on_invalid_param_mode(): void
    {
        $this->expectExceptionObject(Check::invalidModeException(0));
        
        Filters::onlyIn([false], 0)->isAllowed('a', 'a');
    }
    
    public function test_OnlyIn_can_compare_key_for_nonhashmap_values(): void
    {
        self::assertTrue(Filters::onlyIn([[1], [2], ['a']], Check::KEY)->isAllowed([5], ['a']));
    }
    
    public function test_OnlyIn_can_compare_both_key_and_value_for_nonhashmap_values(): void
    {
        $filter = Filters::onlyIn([[1], ['a']], Check::BOTH);
        
        self::assertTrue($filter->isAllowed([1], ['a']));
        self::assertFalse($filter->isAllowed([5], ['a']));
        self::assertFalse($filter->isAllowed([1], ['c']));
    }
    
    public function test_OnlyIn_can_compare_any_key_or_value_for_nonhashmap_values(): void
    {
        $filter = Filters::onlyIn([[1], ['a']], Check::ANY);
        
        self::assertTrue($filter->isAllowed([1], ['a']));
        self::assertTrue($filter->isAllowed([5], ['a']));
        self::assertTrue($filter->isAllowed([1], ['c']));
        self::assertFalse($filter->isAllowed([5], ['c']));
    }
    
    public function test_Length_throws_exception_on_invalid_mode(): void
    {
        $this->expectExceptionObject(Check::invalidModeException(0));
        
        Filters::length(0);
    }
    
    public function test_FilterBy_throws_exception_on_invalid_param_field(): void
    {
        $this->expectExceptionObject(InvalidParamException::describe('field', false));
        
        Filters::filterBy(false, 'is_int');
    }
    
    public function test_OnlyWith_throws_exception_when_param_fields_is_invalid(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('fields'));
        
        Filters::onlyWith(false);
    }
    
    public function test_OnlyWith_throws_exception_when_param_fields_is_empty(): void
    {
        $this->expectExceptionObject(FilterExceptionFactory::paramFieldsCannotBeEmpty());
        
        Filters::onlyWith([]);
    }
    
    /**
     * @dataProvider getDataForTestOnlyWithThrowsExceptionWhenModeIsUnsupported
     */
    public function test_OnlyWith_throws_exception_when_mode_is_unsupported(int $mode): void
    {
        $this->expectExceptionObject(FilterExceptionFactory::modeNotSupportedYet($mode));
        
        Filters::onlyWith('a')->inMode($mode);
    }
    
    public static function getDataForTestOnlyWithThrowsExceptionWhenModeIsUnsupported(): array
    {
        return [[Check::KEY], [Check::BOTH], [Check::ANY]];
    }
    
    public function test_OnlyWith_returns_false_for_each_unrecognizable_argument(): void
    {
        $filter = Filters::onlyWith('key');
        
        self::assertFalse($filter->isAllowed('aaa'));
    }
    
    public function test_OnlyWith_can_handle_ArrayAccess_argument(): void
    {
        $withKey = new \ArrayObject(['key' => 1]);
        $withoutKey = new \ArrayObject(['other' => 1]);
        
        $filter = Filters::onlyWith('key', true);
        
        self::assertTrue($filter->isAllowed($withKey));
        self::assertFalse($filter->isAllowed($withoutKey));
    }
    
    public function test_IsNull_throws_exception_when_mode_is_invalid(): void
    {
        $this->expectExceptionObject(Check::invalidModeException(5));
    
        Filters::isNull(5);
    }
    
    public function test_isBool_all_variations(): void
    {
        $this->examineFilter(static fn(int $mode): Filter => Filters::isBool($mode), true, 'foo');
    }
    
    public function test_isInt_all_variations(): void
    {
        $this->examineFilter(static fn(int $mode): Filter => Filters::number($mode)->isInt(), 1, 'foo');
    }
    
    public function test_isFloat_all_variations(): void
    {
        $this->examineFilter(static fn(int $mode): Filter => Filters::number($mode)->isFloat(), 1.0, 5);
    }
    
    public function test_isCountable_all_variations(): void
    {
        $this->examineFilter(static fn(int $mode): Filter => Filters::isCountable($mode), ['a'], 5);
    }
    
    public function test_isDateTime_all_variations(): void
    {
        $this->examineFilter(static fn(int $mode): Filter => Filters::isDateTime($mode), 'now', false);
    }
    
    public function test_isEmpty_all_variations(): void
    {
        $this->examineFilter(static fn(int $mode): Filter => Filters::isEmpty($mode), '', 1);
    }
    
    public function test_isNull_all_variations(): void
    {
        $this->examineFilter(static fn(int $mode): Filter => Filters::isNull($mode), null, 1);
    }
    
    public function test_isNumeric_all_variations(): void
    {
        $this->examineFilter(static fn(int $mode): Filter => Filters::number($mode)->isNumber(), '15.5', 'foo');
    }
    
    public function test_isString_all_variations(): void
    {
        $this->examineFilter(static fn(int $mode): Filter => Filters::isString($mode), 'foo', 15);
    }
    
    public function test_notEmpty_all_variations(): void
    {
        $this->examineFilter(static fn(int $mode): Filter => Filters::notEmpty($mode), 'foo', 0);
    }
    
    public function test_notNull_all_variations(): void
    {
        $this->examineFilter(static fn(int $mode): Filter => Filters::notNull($mode), 'foo', null);
    }
    
    public function test_time_is_all_variations(): void
    {
        $time = '2015-05-05 12:12:12';
        $wrongTime = '2020-01-30 10:00:00';
        
        $this->examineFilter(static fn(int $mode): Filter => Filters::time($mode)->is($time), $time, $wrongTime);
    }
    
    public function test_time_isNot_all_variations(): void
    {
        $time = '2015-05-05 12:12:12';
        $wrongTime = '2020-01-30 10:00:00';
        
        $this->examineFilter(static fn(int $mode): Filter => Filters::time($mode)->isNot($time), $wrongTime, $time);
    }
    
    public function test_time_before_all_variations(): void
    {
        [$d1, $d2] = $this->dates();
        
        $this->examineFilter(static fn(int $mode): Filter => Filters::time($mode)->before($d2), $d1, $d2);
    }
    
    public function test_time_from_all_variations(): void
    {
        [, , $d3, $d4] = $this->dates();
        
        $this->examineFilter(static fn(int $mode): Filter => Filters::time($mode)->from($d4), $d4, $d3);
    }
    
    public function test_time_after_all_variations(): void
    {
        [, $d2, $d3] = $this->dates();
        
        $this->examineFilter(static fn(int $mode): Filter => Filters::time($mode)->after($d2), $d3, $d2);
    }
    
    public function test_time_until_all_variations(): void
    {
        [$d1, $d2, $d3] = $this->dates();
        
        $this->examineFilter(static fn(int $mode): Filter => Filters::time($mode)->until($d2), $d1, $d3);
    }
    
    public function test_time_between_all_variations(): void
    {
        [, $d2, $d3, $d4] = $this->dates();
        
        $this->examineFilter(static fn(int $mode): Filter => Filters::time($mode)->between($d2, $d3), $d3, $d4);
    }
    
    public function test_time_outside_all_variations(): void
    {
        [$d1, $d2, $d3] = $this->dates();
        
        $this->examineFilter(static fn(int $mode): Filter => Filters::time($mode)->outside($d2, $d3), $d1, $d3);
    }
    
    public function test_time_inside_all_variations(): void
    {
        [$d1, $d2, $d3, $d4] = $this->dates();
        
        $this->examineFilter(static fn(int $mode): Filter => Filters::time($mode)->inside($d2, $d4), $d3, $d1);
    }
    
    public function test_time_notInside_all_variations(): void
    {
        [$d1, $d2, $d3, $d4] = $this->dates();
        
        $this->examineFilter(static fn(int $mode): Filter => Filters::time($mode)->notInside($d2, $d4), $d1, $d3);
    }
    
    public function test_time_inSet_all_variations(): void
    {
        [$d1, $d2, $d3, $d4] = $this->dates();
        
        $this->examineFilter(static fn(int $mode): Filter => Filters::time($mode)->inSet([$d1, $d3, $d4]), $d3, $d2);
    }
    
    public function test_time_notInSet_all_variations(): void
    {
        [$d1, $d2, $d3, $d4] = $this->dates();
        
        $this->examineFilter(static fn(int $mode): Filter => Filters::time($mode)->notInSet([$d1, $d3, $d4]), $d2, $d3);
    }
    
    public function test_string_contains_case_sensitive_all_variations(): void
    {
        $this->examineFilter(
            static fn(int $mode): Filter => Filters::string($mode)->contains('foo'), 'aaafooaaa', 'aaaFOOaaa'
        );
    }
    
    public function test_string_contains_case_insensitive_all_variations(): void
    {
        $this->examineFilter(
            static fn(int $mode): Filter => Filters::string($mode)->contains('foo', true), 'aaaFOOaaa', 'aaabaraaa'
        );
    }
    
    public function test_string_notContains_case_sensitive_all_variations(): void
    {
        $this->examineFilter(
            static fn(int $mode): Filter => Filters::string($mode)->notContains('foo'), 'aaaFOOaaa', 'aaafooaaa'
        );
    }
    
    public function test_string_notContains_case_insensitive_all_variations(): void
    {
        $this->examineFilter(
            static fn(int $mode): Filter => Filters::string($mode)->notContains('foo', true), 'aaabaraaa', 'aaaFOOaaa'
        );
    }
    
    public function test_string_endsWith_case_sensitive_all_variations(): void
    {
        $this->examineFilter(
            static fn(int $mode): Filter => Filters::string($mode)->endsWith('foo'), 'aaafoo', 'aaaFOO'
        );
    }
    
    public function test_string_endsWith_case_insensitive_all_variations(): void
    {
        $this->examineFilter(
            static fn(int $mode): Filter => Filters::string($mode)->endsWith('foo', true), 'aaaFOO', 'aaabar'
        );
    }
    
    public function test_string_notEndsWith_case_sensitive_all_variations(): void
    {
        $this->examineFilter(
            static fn(int $mode): Filter => Filters::string($mode)->notEndsWith('foo'), 'oo', 'aaafoo'
        );
    }
    
    public function test_string_notEndsWith_case_insensitive_all_variations(): void
    {
        $this->examineFilter(
            static fn(int $mode): Filter => Filters::string($mode)->notEndsWith('foo', true), 'oo', 'aaaFOO'
        );
    }
    
    public function test_string_inSet_case_sensitive_all_variations(): void
    {
        $this->examineFilter(
            static fn(int $mode): Filter => Filters::string($mode)->inSet(['aaa', 'foo', 'bbb']), 'foo', 'FOO'
        );
    }
    
    public function test_string_inSet_case_insensitive_all_variations(): void
    {
        $this->examineFilter(
            static fn(int $mode): Filter => Filters::string($mode)->inSet(['aaa', 'foo', 'bbb'], true), 'Foo', 'zoo'
        );
    }
    
    public function test_string_notInSet_case_sensitive_all_variations(): void
    {
        $this->examineFilter(
            static fn(int $mode): Filter => Filters::string($mode)->notInSet(['aaa', 'foo', 'bbb']), 'FOO', 'foo'
        );
    }
    
    public function test_string_notInSet_case_insensitive_all_variations(): void
    {
        $this->examineFilter(
            static fn(int $mode): Filter => Filters::string($mode)->notInSet(['aaa', 'foo', 'bbb'], true), 'zoo', 'Foo'
        );
    }
    
    public function test_string_is_case_sensitive_all_variations(): void
    {
        $this->examineFilter(static fn(int $mode): Filter => Filters::string($mode)->is('foo'), 'foo', 'FOO');
    }
    
    public function test_string_is_case_insensitive_all_variations(): void
    {
        $this->examineFilter(static fn(int $mode): Filter => Filters::string($mode)->is('foo', true), 'Foo', 'zoo');
    }
    
    public function test_string_isNot_case_sensitive_all_variations(): void
    {
        $this->examineFilter(static fn(int $mode): Filter => Filters::string($mode)->isNot('foo'), 'FOO', 'foo');
    }
    
    public function test_string_isNot_case_insensitive_all_variations(): void
    {
        $this->examineFilter(static fn(int $mode): Filter => Filters::string($mode)->isNot('foo', true), 'zoo', 'Foo');
    }
    
    public function test_string_startsWith_case_sensitive_all_variations(): void
    {
        $this->examineFilter(static fn(int $mode): Filter => Filters::string($mode)->startsWith('foo'), 'foo', 'FOO');
    }
    
    public function test_string_startsWith_case_insensitive_all_variations(): void
    {
        $this->examineFilter(
            static fn(int $mode): Filter => Filters::string($mode)->startsWith('foo', true), 'Foo', 'zoo'
        );
    }
    
    public function test_string_notStartsWith_case_sensitive_all_variations(): void
    {
        $this->examineFilter(
            static fn(int $mode): Filter => Filters::string($mode)->notStartsWith('foo'), 'FOO', 'foo'
        );
    }
    
    public function test_string_notStartsWith_case_insensitive_all_variations(): void
    {
        $this->examineFilter(
            static fn(int $mode): Filter => Filters::string($mode)->notStartsWith('foo', true), 'zoo', 'Foo'
        );
    }
    
    public function test_size_count_equal_all_variations(): void
    {
        $this->examineFilter(static fn(int $mode): Filter => Filters::size($mode)->eq(1), ['a'], []);
    }

    public function test_size_count_notEqual_all_variations(): void
    {
        $this->examineFilter(static fn(int $mode): Filter => Filters::size($mode)->ne(1), [], ['a']);
    }
    
    public function test_size_count_lessThan_all_variations(): void
    {
        $this->examineFilter(static fn(int $mode): Filter => Filters::size($mode)->lt(1), [], ['a']);
    }
    
    public function test_size_count_lessOrEqual_all_variations(): void
    {
        $this->examineFilter(static fn(int $mode): Filter => Filters::size($mode)->le(1), ['a'], [1, 2]);
    }
    
    public function test_size_count_greaterThan_all_variations(): void
    {
        $this->examineFilter(static fn(int $mode): Filter => Filters::size($mode)->gt(0), ['a'], []);
    }
    
    public function test_size_count_greaterOrEqual_all_variations(): void
    {
        $this->examineFilter(static fn(int $mode): Filter => Filters::size($mode)->ge(1), ['a'], []);
    }
    
    public function test_size_count_between_all_variations(): void
    {
        $this->examineFilter(static fn(int $mode): Filter => Filters::size($mode)->between(1, 2), [1], [1,2,3]);
    }
    
    public function test_size_count_outside_all_variations(): void
    {
        $this->examineFilter(static fn(int $mode): Filter => Filters::size($mode)->outside(1, 2), [1,2,3], [1,2]);
    }
    
    public function test_size_count_inside_all_variations(): void
    {
        $this->examineFilter(static fn(int $mode): Filter => Filters::size($mode)->inside(1, 3), [1,2], [1,2,3]);
    }
    
    public function test_size_count_notInside_all_variations(): void
    {
        $this->examineFilter(static fn(int $mode): Filter => Filters::size($mode)->notInside(1, 3), [1,2,3], [1,2]);
    }
    
    public function test_size_length_equal_all_variations(): void
    {
        $this->examineFilter(static fn(int $mode): Filter => Filters::length($mode)->eq(1), 'a', 'bb');
    }

    public function test_size_length_notEqual_all_variations(): void
    {
        $this->examineFilter(static fn(int $mode): Filter => Filters::length($mode)->ne(1), 'bb', 'a');
    }
    
    public function test_size_length_lessThan_all_variations(): void
    {
        $this->examineFilter(static fn(int $mode): Filter => Filters::length($mode)->lt(2), 'a', 'bb');
    }
    
    public function test_size_length_lessOrEqual_all_variations(): void
    {
        $this->examineFilter(static fn(int $mode): Filter => Filters::length($mode)->le(1), 'a', 'ab');
    }
    
    public function test_size_length_greaterThan_all_variations(): void
    {
        $this->examineFilter(static fn(int $mode): Filter => Filters::length($mode)->gt(1), 'bb', 'a');
    }
    
    public function test_size_length_greaterOrEqual_all_variations(): void
    {
        $this->examineFilter(static fn(int $mode): Filter => Filters::length($mode)->ge(2), 'aa', 'b');
    }
    
    public function test_size_length_between_all_variations(): void
    {
        $this->examineFilter(static fn(int $mode): Filter => Filters::length($mode)->between(1, 2), 'a', 'abc');
    }
    
    public function test_size_length_outside_all_variations(): void
    {
        $this->examineFilter(static fn(int $mode): Filter => Filters::length($mode)->outside(1, 2), 'abc', 'ab');
    }
    
    public function test_size_length_inside_all_variations(): void
    {
        $this->examineFilter(static fn(int $mode): Filter => Filters::length($mode)->inside(1, 3), 'ab', 'abc');
    }
    
    public function test_size_length_notInside_all_variations(): void
    {
        $this->examineFilter(static fn(int $mode): Filter => Filters::length($mode)->notInside(1, 3), 'abc', 'ab');
    }
    
    public function test_equal_all_variations(): void
    {
        $this->examineFilter(static fn(int $mode): Filter => Filters::equal(3, $mode), '3', 5);
    }
    
    public function test_notEqual_all_variations(): void
    {
        $this->examineFilter(static fn(int $mode): Filter => Filters::notEqual(3, $mode), 5, '3');
    }
    
    public function test_same_all_variations(): void
    {
        $this->examineFilter(static fn(int $mode): Filter => Filters::same(3, $mode), 3, '3');
    }
    
    public function test_notSame_all_variations(): void
    {
        $this->examineFilter(static fn(int $mode): Filter => Filters::notSame(3, $mode), '3', 3);
    }
    
    public function test_number_between_all_variations(): void
    {
        $this->examineFilter(static fn(int $mode): Filter => Filters::number($mode)->between(2, 3), 2, 1);
    }
    
    public function test_number_outside_all_variations(): void
    {
        $this->examineFilter(static fn(int $mode): Filter => Filters::number($mode)->outside(2, 3), 1, 3);
    }
    
    public function test_number_equal_all_variations(): void
    {
        $this->examineFilter(static fn(int $mode): Filter => Filters::number($mode)->eq(1), 1, 2);
    }
    
    public function test_number_notEqual_all_variations(): void
    {
        $this->examineFilter(static fn(int $mode): Filter => Filters::number($mode)->ne(1), 2, 1);
    }
    
    public function test_number_greaterOrEqual_all_variations(): void
    {
        $this->examineFilter(static fn(int $mode): Filter => Filters::number($mode)->ge(2), 2, 1);
    }
    
    public function test_number_lessThan_all_variations(): void
    {
        $this->examineFilter(static fn(int $mode): Filter => Filters::number($mode)->lt(2), 1, 2);
    }
    
    public function test_number_greaterThan_all_variations(): void
    {
        $this->examineFilter(static fn(int $mode): Filter => Filters::number($mode)->gt(1), 2, 1);
    }
    
    public function test_number_lessOrEqual_all_variations(): void
    {
        $this->examineFilter(static fn(int $mode): Filter => Filters::number($mode)->le(1), 1, 2);
    }
    
    public function test_number_inside_all_variations(): void
    {
        $this->examineFilter(static fn(int $mode): Filter => Filters::number($mode)->inside(1, 2), 1.5, 1);
    }
    
    public function test_number_notInside_all_variations(): void
    {
        $this->examineFilter(static fn(int $mode): Filter => Filters::number($mode)->notInside(1, 2), 1, 1.5);
    }
    
    public function test_number_isEven_all_variations(): void
    {
        $this->examineFilter(static fn(int $mode): Filter => Filters::number($mode)->isEven(), 4, 3);
    }
    
    public function test_number_isOdd_all_variations(): void
    {
        $this->examineFilter(static fn(int $mode): Filter => Filters::number($mode)->isOdd(), 3, 4);
    }
    
    public function test_onlyIn_ints_all_variations(): void
    {
        $this->examineFilter(static fn(int $mode): Filter => Filters::onlyIn([1, 3], $mode), 3, 2);
    }
    
    public function test_onlyIn_strings_all_variations(): void
    {
        $this->examineFilter(static fn(int $mode): Filter => Filters::onlyIn(['a', 'b'], $mode), 'a', 'c');
    }
    
    public function test_onlyIn_others_all_variations(): void
    {
        $this->examineFilter(static fn(int $mode): Filter => Filters::onlyIn([1.0, 2.0], $mode), 1.0, 1.5);
    }
    
    public function test_onlyIn_mixed_all_variations(): void
    {
        $this->examineFilter(static fn(int $mode): Filter => Filters::onlyIn([1, '1', 1.0], $mode), '1', 2);
    }
    
    public function test_one_arg_callable_all_variations(): void
    {
        $this->examineFilter(
            static fn(int $mode): Filter => Filters::getAdapter(static fn($val): bool => $val === 1, $mode), 1, 2
        );
    }
    
    public function test_isDay_all_variations(): void
    {
        $this->examineFilter(
            static fn(int $mode): Filter => Filters::time($mode)->isDay(Day::FRI), '2024-04-19', '2024-04-20'
        );
    }
    
    public function test_isNotDay_all_variations(): void
    {
        $this->examineFilter(
            static fn(int $mode): Filter => Filters::time($mode)->isNotDay(Day::FRI), '2024-04-20', '2024-04-19'
        );
    }
    
    private function examineFilter(callable $factory, $goodValue, $wrongValue): void
    {
        $iterator = $this->filterTesterIterator($factory, $goodValue, $wrongValue);
        
        /* @var $filter Filter */
        foreach ($iterator as $case => [$filter, $pattern, $good, $wrong]) {
            self::assertSame($pattern[0], $filter->isAllowed($good, $good), $case.'_0');
            self::assertSame($pattern[1], $filter->isAllowed($good, $wrong), $case.'_1');
            self::assertSame($pattern[2], $filter->isAllowed($wrong, $good), $case.'_2');
            self::assertSame($pattern[3], $filter->isAllowed($wrong, $wrong), $case.'_3');
        }
    }
    
    private function filterTesterIterator(callable $factory, $good, $wrong): iterable
    {
        $patterns = [
            'value' => [true, true, false, false],
            'key' => [true, false, true, false],
            'both' => [true, false, false, false],
            'any' => [true, true, true, false],
        ];
        
        $filters = [
            'value' => $factory(Check::VALUE),
            'key' => $factory(Check::KEY),
            'both' => $factory(Check::BOTH),
            'any' => $factory(Check::ANY),
        ];
        
        foreach (['value', 'key', 'both', 'any'] as $mode) {
            yield $mode => [
                $filters[$mode],
                $patterns[$mode],
                $good,
                $wrong,
            ];
        }
    }
    
    public function test_IsFloat_throws_exception_when_mode_is_invalid(): void
    {
        $this->expectExceptionObject(Check::invalidModeException(5));
    
        Filters::isFloat(5);
    }
    
    public function test_IsBool_throws_exception_when_mode_is_invalid(): void
    {
        $this->expectExceptionObject(Check::invalidModeException(5));
    
        Filters::isBool(5);
    }
    
    public function test_onlyWith_allow_nulls(): void
    {
        $good = ['a' => null];
        $wrong = ['b' => 1];
        
        $filter = Filters::onlyWith('a', true);
        
        self::assertTrue($filter->isAllowed($good));
        self::assertFalse($filter->isAllowed($wrong));
        
        $negation = Filters::NOT($filter);
        
        self::assertFalse($negation->isAllowed($good));
        self::assertTrue($negation->isAllowed($wrong));
    }
    
    public function test_onlyWith_disallow_nulls(): void
    {
        $good = ['a' => 1];
        $wrong = ['a' => null];
        
        $filter = Filters::onlyWith('a');
        
        self::assertTrue($filter->isAllowed($good));
        self::assertFalse($filter->isAllowed($wrong));
        
        $negation = Filters::NOT($filter);
        
        self::assertFalse($negation->isAllowed($good));
        self::assertTrue($negation->isAllowed($wrong));
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
    }
    
    /**
     * @dataProvider getDataForTestFilterThrowsExceptionWhenParamModeIsInvalid
     */
    public function test_filter_throws_exception_when_param_mode_is_invalid(callable $factory): void
    {
        $this->expectExceptionObject(Check::invalidModeException(5));
        
        $factory(5);
    }
    
    public static function getDataForTestFilterThrowsExceptionWhenParamModeIsInvalid(): iterable
    {
        yield 'Filters_string' => [static fn(int $mode) => Filters::string($mode)];
        yield 'String_Contains' => [static fn(int $mode) => Contains::create($mode, 'a')];
        yield 'String_StartsWith' => [static fn(int $mode) => StartsWith::create($mode, 'a')];
        yield 'String_EndsWith' => [static fn(int $mode) => EndsWith::create($mode, 'a')];
        yield 'String_InSet' => [static fn(int $mode) => InSet::create($mode, ['a'])];
        yield 'String_StrIs' => [static fn(int $mode) => StrIs::create($mode, 'a')];
        
        yield 'String_NotContains' => [static fn(int $mode) => NotContains::create($mode, 'a')];
        yield 'String_NotStartsWith' => [static fn(int $mode) => NotStartsWith::create($mode, 'a')];
        yield 'String_NotEndsWith' => [static fn(int $mode) => NotEndsWith::create($mode, 'a')];
        yield 'String_NotInSet' => [static fn(int $mode) => NotInSet::create($mode, ['a'])];
        yield 'String_StrIsNot' => [static fn(int $mode) => StrIsNot::create($mode, 'a')];
        
        yield 'TimeFilter' => [static fn(int $mode) => TimeFilter::create($mode, new Is('now'))];
        yield 'CountFilter' => [static fn(int $mode) => CountFilter::create($mode, Filters::number()->eq(1))];
        yield 'LengthFilter' => [static fn(int $mode) => LengthFilter::create($mode, Filters::number()->eq(1))];
        
        yield 'Equal' => [static fn(int $mode) => Filters::equal(1, $mode)];
        yield 'NotEqual' => [static fn(int $mode) => Filters::notEqual(1, $mode)];
        
        yield 'Same' => [static fn(int $mode) => Filters::same(1, $mode)];
        yield 'NotSame' => [static fn(int $mode) => Filters::notSame(1, $mode)];
        
        yield 'Number_Between' => [static fn(int $mode): Filter => Between::create($mode, 1, 3)];
        yield 'Number_Outside' => [static fn(int $mode): Filter => Outside::create($mode, 1, 3)];
        yield 'Number_Equal' => [static fn(int $mode): Filter => Equal::create($mode, 1)];
        yield 'Number_NotEqual' => [static fn(int $mode): Filter => NotEqual::create($mode, 1)];
        yield 'Number_GreaterOrEqual' => [static fn(int $mode): Filter => GreaterOrEqual::create($mode, 1)];
        yield 'Number_LessThan' => [static fn(int $mode): Filter => LessThan::create($mode, 1)];
        yield 'Number_GreaterThan' => [static fn(int $mode): Filter => GreaterThan::create($mode, 1)];
        yield 'Number_LessOrEqual' => [static fn(int $mode): Filter => LessOrEqual::create($mode, 1)];
        yield 'Number_Inside' => [static fn(int $mode): Filter => Inside::create($mode, 1, 2)];
        yield 'Number_NotInside' => [static fn(int $mode): Filter => NotInside::create($mode, 1, 2)];
        yield 'Number_IsEven' => [static fn(int $mode): Filter => IsEven::create($mode)];
        yield 'Number_IsOdd' => [static fn(int $mode): Filter => IsOdd::create($mode)];
        
        yield 'OnlyIn' => [static fn(int $mode): Filter => OnlyIn::create($mode, ['a'])];
    }
    
    public function test_string_filters(): void
    {
        self::assertFalse(Filters::string()->contains('very long string')->isAllowed('long'));
        self::assertFalse(Filters::string()->startsWith('very long string')->isAllowed('very long'));
        self::assertFalse(Filters::string()->endsWith('very long string')->isAllowed('long string'));
        
        self::assertTrue(Filters::string()->contains('long string')->isAllowed('long string'));
        self::assertTrue(Filters::string()->startsWith('long string')->isAllowed('long string'));
        self::assertTrue(Filters::string()->endsWith('long string')->isAllowed('long string'));
        
        self::assertFalse(Filters::string(Check::KEY)->endsWith('long string')->isAllowed('long string', 'string'));
    }
    
    public function test_IsEven(): void
    {
        $value = Filters::number()->isEven();
        $key = Filters::number(Check::KEY)->isEven();
        $both = Filters::number(Check::BOTH)->isEven();
        $any = Filters::number(Check::ANY)->isEven();
    
        self::assertTrue($value->isAllowed(2, 1));
        self::assertFalse($key->isAllowed(2, 1));
        self::assertFalse($both->isAllowed(2, 1));
        self::assertTrue($any->isAllowed(2, 1));
    
        self::assertFalse($value->isAllowed(1, 2));
        self::assertTrue($key->isAllowed(1, 2));
        self::assertFalse($both->isAllowed(1, 2));
        self::assertTrue($any->isAllowed(1, 2));
    
        self::assertTrue($value->isAllowed(2, 2));
        self::assertTrue($key->isAllowed(2, 2));
        self::assertTrue($both->isAllowed(2, 2));
        self::assertTrue($any->isAllowed(2, 2));
    
        self::assertFalse($value->isAllowed(1, 1));
        self::assertFalse($key->isAllowed(1, 1));
        self::assertFalse($both->isAllowed(1, 1));
        self::assertFalse($any->isAllowed(1, 1));
        
        self::assertTrue($value->isAllowed(-2));
        self::assertFalse($value->isAllowed(-1));
    }
    
    public function test_IsOdd(): void
    {
        $value = Filters::number()->isOdd();
        $key = Filters::number(Check::KEY)->isOdd();
        $both = Filters::number(Check::BOTH)->isOdd();
        $any = Filters::number(Check::ANY)->isOdd();
    
        self::assertTrue($value->isAllowed(1, 2));
        self::assertFalse($key->isAllowed(1, 2));
        self::assertFalse($both->isAllowed(1, 2));
        self::assertTrue($any->isAllowed(1, 2));
    
        self::assertFalse($value->isAllowed(2, 1));
        self::assertTrue($key->isAllowed(2, 1));
        self::assertFalse($both->isAllowed(2, 1));
        self::assertTrue($any->isAllowed(2, 1));
    
        self::assertTrue($value->isAllowed(1, 1));
        self::assertTrue($key->isAllowed(1, 1));
        self::assertTrue($both->isAllowed(1, 1));
        self::assertTrue($any->isAllowed(1, 1));
    
        self::assertFalse($value->isAllowed(2, 2));
        self::assertFalse($key->isAllowed(2, 2));
        self::assertFalse($both->isAllowed(2, 2));
        self::assertFalse($any->isAllowed(2, 2));
        
        self::assertFalse($value->isAllowed(-2));
        self::assertTrue($value->isAllowed(-1));
    }
    
    public function test_FilterAND_throws_exception_when_list_of_filters_is_empty(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('filters'));
        
        Filters::AND();
    }
    
    public function test_FilterOR_throws_exception_when_list_of_filters_is_empty(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('filters'));
        
        Filters::OR();
    }
    
    public function test_FilterAND(): void
    {
        $value = Filters::AND(Filters::greaterOrEqual(5), Filters::lessOrEqual(10));
        $key = $value->inMode(Check::KEY);
        $both = $value->inMode(Check::BOTH);
        $any = $value->inMode(Check::ANY);
        
        self::assertTrue($value->isAllowed(8, 7));
        self::assertFalse($value->isAllowed(11, 7));
        self::assertFalse($value->isAllowed(4, 7));
        
        self::assertTrue($key->isAllowed(7, 8));
        self::assertFalse($key->isAllowed(7, 11));
        self::assertFalse($key->isAllowed(7, 4));
        
        self::assertTrue($both->isAllowed(5, 8));
        self::assertFalse($both->isAllowed(7, 11));
        self::assertFalse($both->isAllowed(9, 4));
        self::assertFalse($both->isAllowed(12, 4));
        self::assertFalse($both->isAllowed(4, 12));
        
        self::assertTrue($any->isAllowed(5, 8));
        self::assertTrue($any->isAllowed(7, 11));
        self::assertTrue($any->isAllowed(4, 9));
        self::assertFalse($any->isAllowed(11, 4));
        self::assertFalse($any->isAllowed(4, 11));
    }
    
    public function test_FilterOR(): void
    {
        $value = Filters::OR(Filters::lessOrEqual(5), Filters::greaterOrEqual(10));
        $key = $value->inMode(Check::KEY);
        $both = $value->inMode(Check::BOTH);
        $any = $value->inMode(Check::ANY);
        
        self::assertFalse($value->isAllowed(8, 7));
        self::assertTrue($value->isAllowed(11, 7));
        self::assertTrue($value->isAllowed(4, 7));
        
        self::assertFalse($key->isAllowed(7, 8));
        self::assertTrue($key->isAllowed(7, 11));
        self::assertTrue($key->isAllowed(7, 4));
        
        self::assertFalse($both->isAllowed(5, 8));
        self::assertFalse($both->isAllowed(7, 11));
        self::assertFalse($both->isAllowed(9, 4));
        self::assertTrue($both->isAllowed(12, 4));
        self::assertTrue($both->isAllowed(4, 12));
        
        self::assertFalse($any->isAllowed(7, 8));
        self::assertTrue($any->isAllowed(5, 8));
        self::assertTrue($any->isAllowed(7, 11));
        self::assertTrue($any->isAllowed(4, 9));
        self::assertTrue($any->isAllowed(11, 4));
        self::assertTrue($any->isAllowed(4, 11));
    }
    
    public function test_FilterNOT(): void
    {
        $value = Filters::NOT(Filters::greaterThan(5));
        $key = $value->inMode(Check::KEY);
        $both = $value->inMode(Check::BOTH);
        $any = $value->inMode(Check::ANY);
        
        self::assertTrue($value->isAllowed(5, 0));
        self::assertFalse($value->isAllowed(6, 0));
        
        self::assertTrue($key->isAllowed(0, 5));
        self::assertFalse($key->isAllowed(0, 6));
        
        self::assertTrue($both->isAllowed(5, 0));
        self::assertTrue($both->isAllowed(0, 5));
        self::assertFalse($both->isAllowed(0, 6));
        self::assertFalse($both->isAllowed(7, 0));
        self::assertFalse($both->isAllowed(7, 5));
        self::assertFalse($both->isAllowed(8, 8));
        
        self::assertTrue($any->isAllowed(7, 5));
        self::assertTrue($any->isAllowed(5, 7));
        self::assertFalse($any->isAllowed(8, 7));
    }
    
    public function test_FilterXOR(): void
    {
        $value = Filters::XOR(Filters::greaterOrEqual(5), Filters::lessOrEqual(10));
        $key = $value->inMode(Check::KEY);
        $both = $value->inMode(Check::BOTH);
        $any = $value->inMode(Check::ANY);
        
        self::assertTrue($value->isAllowed(12, 0));
        self::assertTrue($value->isAllowed(3, 0));
        self::assertFalse($value->isAllowed(7, 0));
        
        self::assertTrue($key->isAllowed(0, 12));
        self::assertTrue($key->isAllowed(0, 3));
        self::assertFalse($key->isAllowed(0, 7));
        
        self::assertTrue($both->isAllowed(0, 12));
        self::assertTrue($both->isAllowed(12, 0));
        self::assertFalse($both->isAllowed(0, 7));
        self::assertFalse($both->isAllowed(7, 0));
        
        self::assertTrue($any->isAllowed(0, 12));
        self::assertTrue($any->isAllowed(12, 0));
        self::assertTrue($any->isAllowed(7, 12));
        self::assertTrue($any->isAllowed(12, 7));
        self::assertFalse($any->isAllowed(6, 7));
    }
    
    public function test_FilterXNOR(): void
    {
        $value = Filters::XNOR(Filters::greaterOrEqual(5), Filters::lessOrEqual(10));
        $key = $value->inMode(Check::KEY);
        $both = $value->inMode(Check::BOTH);
        $any = $value->inMode(Check::ANY);
        
        self::assertFalse($value->isAllowed(12, 0));
        self::assertFalse($value->isAllowed(3, 0));
        self::assertFalse($value->isAllowed(0, 0));
        self::assertTrue($value->isAllowed(7, 0));
        
        self::assertFalse($key->isAllowed(0, 12));
        self::assertFalse($key->isAllowed(0, 3));
        self::assertFalse($key->isAllowed(0, 0));
        self::assertTrue($key->isAllowed(0, 7));
        
        self::assertFalse($both->isAllowed(0, 12));
        self::assertFalse($both->isAllowed(12, 0));
        self::assertFalse($both->isAllowed(7, 0));
        self::assertFalse($both->isAllowed(0, 7));
        self::assertTrue($both->isAllowed(9, 7));
        self::assertTrue($both->isAllowed(7, 9));

        self::assertFalse($any->isAllowed(0, 3));
        self::assertFalse($any->isAllowed(3, 0));
        self::assertTrue($any->isAllowed(0, 7));
        self::assertTrue($any->isAllowed(7, 0));
        self::assertTrue($any->isAllowed(7, 9));
    }
    
    public function test_Between_throws_exception_when_param_lower_is_invalid(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('lower'));
        
        Filters::number()->between('a', 4);
    }
    
    public function test_Between_throws_exception_when_param_higher_is_invalid(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('higher'));
        
        Filters::number()->between(4, 'a');
    }
    
    public function test_Between_throws_exception_when_param_lower_is_greater_then_higher(): void
    {
        $this->expectExceptionObject(FilterExceptionFactory::paramLowerCannotBeGreaterThanHigher());
        
        Filters::number()->between(4, 2);
    }
    
    public function test_NumberFactory_throws_exception_when_param_mode_is_invalid(): void
    {
        $this->expectExceptionObject(Check::invalidModeException(0));
        
        Filters::number(0);
    }
    
    public function test_hasField(): void
    {
        $hasField = Filters::hasField('foo');
        
        self::assertFalse($hasField->isAllowed(15));
        self::assertFalse($hasField->isAllowed([]));
        
        self::assertFalse($hasField->isAllowed(['foo' => null]));
        self::assertTrue($hasField->isAllowed(['foo' => 1]));
    }
    
    public function test_OnlyIn_hashMap_strings(): void
    {
        $filter = Filters::onlyIn(['foo', 'bar']);
        
        self::assertTrue($filter->isAllowed('foo', 5));
        self::assertFalse($filter->inMode(Check::KEY)->isAllowed('foo', 5));
        self::assertFalse($filter->inMode(Check::BOTH)->isAllowed('foo', 5));
        self::assertTrue($filter->inMode(Check::ANY)->isAllowed('foo', 5));
    }
    
    public function test_OnlyIn_hashMap_ints(): void
    {
        $filter = Filters::onlyIn([5, 3]);
        
        self::assertFalse($filter->isAllowed('foo', 5));
        self::assertTrue($filter->inMode(Check::KEY)->isAllowed('foo', 5));
        self::assertFalse($filter->inMode(Check::BOTH)->isAllowed('foo', 5));
        self::assertTrue($filter->inMode(Check::ANY)->isAllowed('foo', 5));
    }
    
    public function test_TimeFilter_Between_throws_exception_when_date_from_is_greater_than_date_until(): void
    {
        $this->expectExceptionObject(FilterExceptionFactory::paramFromIsGreaterThanUntil());
        
        Filters::time()->between('2024-01-01', '2012-01-01');
    }
    
    public function test_TimeFilter_Outside_throws_exception_when_date_from_is_greater_than_date_until(): void
    {
        $this->expectExceptionObject(FilterExceptionFactory::paramFromIsGreaterThanUntil());
        
        Filters::time()->outside('2024-01-01', '2012-01-01');
    }
    
    public function test_TimeFilter_Inside_with_always_false_arguments(): void
    {
        [$d1, $d2, $d3, $d4, $d5] = $this->dates();
        
        $filter = Filters::time()->inside($d3, $d3);
        
        self::assertFalse($filter->isAllowed($d1));
        self::assertFalse($filter->isAllowed($d2));
        self::assertFalse($filter->isAllowed($d3));
        self::assertFalse($filter->isAllowed($d4));
        self::assertFalse($filter->isAllowed($d5));
    }
    
    public function test_FilterNOT_TimeFilter_Inside_with_always_false_arguments(): void
    {
        [$d1, $d2, $d3, $d4, $d5] = $this->dates();
        
        $filter = Filters::NOT(Filters::time()->inside($d3, $d3));
        
        self::assertTrue($filter->isAllowed($d1));
        self::assertTrue($filter->isAllowed($d2));
        self::assertTrue($filter->isAllowed($d3));
        self::assertTrue($filter->isAllowed($d4));
        self::assertTrue($filter->isAllowed($d5));
    }
    
    /**
     * @dataProvider getDataForTestTimeFiltersThrowsExceptionWhenTestedValueIsNotValidDatetime
     */
    public function test_time_filters_throws_exception_when_tested_value_is_not_valid_datetime(Filter $filter): void
    {
        $time = ['array'];
        
        $this->expectExceptionObject(FilterExceptionFactory::invalidTimeValue($time));
        
        $filter->isAllowed($time);
    }
    
    public static function getDataForTestTimeFiltersThrowsExceptionWhenTestedValueIsNotValidDatetime(): iterable
    {
        $filter = Filters::time();
        
        yield 'from' => [$filter->from('now')];
        yield 'after' => [$filter->after('now')];
        yield 'until' => [$filter->until('now')];
        yield 'before' => [$filter->before('now')];
        yield 'is' => [$filter->is('now')];
        yield 'isNot' => [$filter->isNot('now')];
        yield 'between' => [$filter->between('now', 'now')];
        yield 'outside' => [$filter->outside('now', 'now')];
        yield 'inside' => [$filter->inside('now', 'now')];
        yield 'not_inside' => [$filter->notInside('now', 'now')];
        yield 'inSet' => [$filter->inSet(['now'])];
        yield 'notInSet' => [$filter->notInSet(['now'])];
        yield 'isDay' => [$filter->isDay(Day::MON)];
        yield 'isNotDay' => [$filter->isNotDay(Day::MON)];
    }
    
    public function test_TimeFilter_Between_both_arguments_point_to_the_same_datetime(): void
    {
        [$d1, $d2, $d3] = $this->dates();
        
        $filter = Filters::time()->between($d2, $d2);
        
        self::assertFalse($filter->isAllowed($d1));
        self::assertTrue($filter->isAllowed($d2));
        self::assertFalse($filter->isAllowed($d3));
    }
    
    public function test_TimeFilter_Outside_both_arguments_point_to_the_same_datetime(): void
    {
        [$d1, $d2, $d3] = $this->dates();
        
        $filter = Filters::time()->outside($d2, $d2);
        
        self::assertTrue($filter->isAllowed($d1));
        self::assertFalse($filter->isAllowed($d2));
        self::assertTrue($filter->isAllowed($d3));
    }
    
    /**
     * @dataProvider getDataForTestTimeFiltersThrowExceptionWhenArgumentIsNotValidDatetime
     */
    public function test_time_filters_throw_exception_when_argument_is_not_valid_datetime(callable $method): void
    {
        $time = ['array'];
        
        $this->expectExceptionObject(InvalidParamException::describe('time', $time));
        
        $method($time);
    }
    
    public static function getDataForTestTimeFiltersThrowExceptionWhenArgumentIsNotValidDatetime(): iterable
    {
        $filter = Filters::time();
        
        yield 'from' => [static fn($arg) => $filter->from($arg)];
        yield 'after' => [static fn($arg) => $filter->after($arg)];
        yield 'until' => [static fn($arg) => $filter->until($arg)];
        yield 'before' => [static fn($arg) => $filter->before($arg)];
        yield 'is' => [static fn($arg) => $filter->is($arg)];
        yield 'isNot' => [static fn($arg) => $filter->isNot($arg)];
        yield 'between' => [static fn($arg) => $filter->between($arg, $arg)];
        yield 'outside' => [static fn($arg) => $filter->outside($arg, $arg)];
        yield 'inside' => [static fn($arg) => $filter->inside($arg, $arg)];
        yield 'inSet' => [static fn($arg) => $filter->inSet([$arg])];
        yield 'notInSet' => [static fn($arg) => $filter->notInSet([$arg])];
    }
    
    /**
     * @dataProvider getDataForTestWeekDayFilterThrowsExceptionWhenParamDaysIsInvalid
     */
    public function test_isDay_filter_throws_exception_when_param_days_is_invalid(...$days): void
    {
        $this->expectExceptionObject(InvalidParamException::describe('days', $days));
        
        Filters::time()->isDay(...$days);
    }
    
    /**
     * @dataProvider getDataForTestWeekDayFilterThrowsExceptionWhenParamDaysIsInvalid
     */
    public function test_isNotDay_filter_throws_exception_when_param_days_is_invalid(...$days): void
    {
        $this->expectExceptionObject(InvalidParamException::describe('days', $days));
        
        Filters::time()->isNotDay(...$days);
    }
    
    public static function getDataForTestWeekDayFilterThrowsExceptionWhenParamDaysIsInvalid(): array
    {
        return [
            [],
            [''],
            [1],
            [Day::SAT, 'foo']
        ];
    }
    
    public function test_TimeFilter_empty_inSet(): void
    {
        self::assertEmpty(Stream::from($this->dates())->filter(Filters::time()->inSet([]))->toArray());
    }
    
    public function test_TimeFilter_empty_notInSet(): void
    {
        self::assertEquals(
            $this->dates(),
            Stream::from($this->dates())->filter(Filters::time()->notInSet([]))->toArray()
        );
    }
    
    public function test_FilterNOT_TimeFilter_empty_inSet(): void
    {
        self::assertEquals(
            $this->dates(),
            Stream::from($this->dates())->filter(Filters::NOT(Filters::time()->inSet([])))->toArray()
        );
    }
    
    public function test_FilterNOT_TimeFilter_empty_notInSet(): void
    {
        self::assertEmpty(Stream::from($this->dates())->filter(Filters::NOT(Filters::time()->notInSet([])))->toArray());
    }
    
    private function dates(): array
    {
        $d1 = new \DateTime('2024-01-31 15:00:00');
        $d2 = new \DateTimeImmutable('2024-01-31 15:30:00');
        $d3 = '2024-01-31 16:00:00';
        $d4 = new \DateTimeImmutable('2024-01-31 17:30:00');
        $d5 = '2024-01-31 18:00:00';
        
        return [$d1, $d2, $d3, $d4, $d5];
    }
    
    public function test_IdleTimeComp(): void
    {
        $filter = IdleTimeComp::true();
        
        self::assertTrue($filter->isSatisfiedBy('now'));
        self::assertFalse($filter->negation()->isSatisfiedBy('now'));
    }
    
    public function test_IdleTimeComp_throws_exception_when_tested_value_is_not_valid(): void
    {
        $this->expectExceptionObject(FilterExceptionFactory::invalidTimeValue('foo'));
        
        IdleTimeComp::true()->isSatisfiedBy('foo');
    }
    
    public function test_idle_filter_true(): void
    {
        $filter = IdleFilter::true(Check::BOTH);
        
        self::assertTrue($filter->isAllowed('15', 2));
        self::assertSame(Check::BOTH, $filter->getMode());
        
        $not = $filter->negate();
        
        self::assertFalse($not->isAllowed('15', 2));
        self::assertSame(Check::BOTH, $not->getMode());
    }
    
    public function test_idle_filter_false(): void
    {
        $filter = IdleFilter::false(Check::ANY);
        
        self::assertFalse($filter->isAllowed('15', 2));
        self::assertSame(Check::ANY, $filter->getMode());
        
        $not = $filter->negate();
        
        self::assertTrue($not->isAllowed('15', 2));
        self::assertSame(Check::ANY, $not->getMode());
    }
    
    public function test_idle_filter_in_different_mode(): void
    {
        $key = IdleFilter::false(Check::KEY);
        
        self::assertFalse($key->isAllowed('15', 2));
        self::assertSame(Check::KEY, $key->getMode());
        
        $value = $key->checkValue();
        
        self::assertFalse($value->isAllowed('15', 2));
        self::assertSame(Check::VALUE, $value->getMode());
    }
}