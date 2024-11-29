<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Filter\CheckType\IsArray;
use FiiSoft\Jackdaw\Filter\CheckType\IsBool;
use FiiSoft\Jackdaw\Filter\CheckType\IsCountable;
use FiiSoft\Jackdaw\Filter\CheckType\IsDateTime;
use FiiSoft\Jackdaw\Filter\CheckType\IsEmpty;
use FiiSoft\Jackdaw\Filter\CheckType\IsFloat;
use FiiSoft\Jackdaw\Filter\CheckType\IsInt;
use FiiSoft\Jackdaw\Filter\CheckType\IsNull;
use FiiSoft\Jackdaw\Filter\CheckType\IsNumeric;
use FiiSoft\Jackdaw\Filter\CheckType\IsString;
use FiiSoft\Jackdaw\Filter\CheckType\NotEmpty;
use FiiSoft\Jackdaw\Filter\CheckType\NotNull;
use FiiSoft\Jackdaw\Filter\Generic\GenericFilter;
use FiiSoft\Jackdaw\Filter\Logic\OpAND\BaseAND;
use FiiSoft\Jackdaw\Filter\Logic\OpOR\BaseOR;
use FiiSoft\Jackdaw\Filter\Logic\OpXNOR\BaseXNOR;
use FiiSoft\Jackdaw\Filter\Logic\OpXOR\BaseXOR;
use FiiSoft\Jackdaw\Filter\Number\NumberFilterFactory;
use FiiSoft\Jackdaw\Filter\OnlyIn\OnlyIn;
use FiiSoft\Jackdaw\Filter\OnlyWith\OnlyWith;
use FiiSoft\Jackdaw\Filter\Simple\Equal;
use FiiSoft\Jackdaw\Filter\Simple\NotEqual;
use FiiSoft\Jackdaw\Filter\Simple\NotSame;
use FiiSoft\Jackdaw\Filter\Simple\Same;
use FiiSoft\Jackdaw\Filter\Size\Count\CountFilterFactory;
use FiiSoft\Jackdaw\Filter\Size\Length\LengthFilterFactory;
use FiiSoft\Jackdaw\Filter\String\StringFilterFactory;
use FiiSoft\Jackdaw\Filter\Time\TimeFilterFactory;

final class Filters
{
    /**
     * @param Filter|callable|mixed $filter
     */
    public static function getAdapter($filter, ?int $mode = null): Filter
    {
        if ($filter instanceof Filter) {
            return $filter->inMode($mode);
        }
    
        if (\is_callable($filter)) {
            if (\is_string($filter)) {
                switch ($filter) {
                    case 'is_int':
                    case '\is_int':
                        return self::isInt($mode);
                    case 'is_numeric':
                    case '\is_numeric':
                        return self::isNumeric($mode);
                    case 'is_string':
                    case '\is_string':
                        return self::isString($mode);
                    case 'is_float':
                    case '\is_float':
                        return self::isFloat($mode);
                    case 'is_null':
                    case '\is_null':
                        return self::isNull($mode);
                    case 'is_bool':
                    case '\is_bool':
                        return self::isBool($mode);
                    case 'is_array':
                    case '\is_array':
                        return self::isArray($mode);
                    default:
                        //noop
                }
            }
            
            return GenericFilter::create($filter, $mode);
        }
        
        if (\is_object($filter)) {
            throw InvalidParamException::describe('filter', $filter);
        }
        
        return self::same($filter, $mode);
    }
    
    public static function time(?int $mode = null): TimeFilterFactory
    {
        return TimeFilterFactory::instance($mode);
    }
    
    public static function string(?int $mode = null): StringFilterFactory
    {
        return StringFilterFactory::instance($mode);
    }
    
    public static function size(?int $mode = null): CountFilterFactory
    {
        return CountFilterFactory::instance($mode);
    }
    
    public static function length(?int $mode = null): LengthFilterFactory
    {
        return LengthFilterFactory::instance($mode);
    }
    
    public static function number(?int $mode = null): NumberFilterFactory
    {
        return NumberFilterFactory::instance($mode);
    }
    
    public static function isEmpty(?int $mode = null): Filter
    {
        return IsEmpty::create($mode);
    }
    
    public static function notEmpty(?int $mode = null): Filter
    {
        return NotEmpty::create($mode);
    }
    
    public static function isDateTime(?int $mode = null): Filter
    {
        return IsDateTime::create($mode);
    }
    
    public static function isCountable(?int $mode = null): Filter
    {
        return IsCountable::create($mode);
    }
    
    public static function isNull(?int $mode = null): Filter
    {
        return IsNull::create($mode);
    }
    
    public static function notNull(?int $mode = null): Filter
    {
        return NotNull::create($mode);
    }
    
    /**
     * @param array<string|int, mixed> $values
     */
    public static function onlyIn(array $values, ?int $mode = null): Filter
    {
        return OnlyIn::create($mode, $values);
    }
    
    /**
     * @param mixed $value
     */
    public static function equal($value, ?int $mode = null): Filter
    {
        return Equal::create($mode, $value);
    }
    
    /**
     * @param mixed $value
     */
    public static function notEqual($value, ?int $mode = null): Filter
    {
        return NotEqual::create($mode, $value);
    }
    
    /**
     * @param mixed $value
     */
    public static function same($value, ?int $mode = null): Filter
    {
        return Same::create($mode, $value);
    }
    
    /**
     * @param mixed $value
     */
    public static function notSame($value, ?int $mode = null): Filter
    {
        return NotSame::create($mode, $value);
    }
    
    /**
     * @param float|int $value
     */
    public static function greaterThan($value, ?int $mode = null): Filter
    {
        return self::number($mode)->gt($value);
    }
    
    /**
     * @param float|int $value
     */
    public static function greaterOrEqual($value, ?int $mode = null): Filter
    {
        return self::number($mode)->ge($value);
    }
    
    /**
     * @param float|int $value
     */
    public static function lessThan($value, ?int $mode = null): Filter
    {
        return self::number($mode)->lt($value);
    }
    
    /**
     * @param float|int $value
     */
    public static function lessOrEqual($value, ?int $mode = null): Filter
    {
        return self::number($mode)->le($value);
    }
    
    public static function isInt(?int $mode = null): Filter
    {
        return IsInt::create($mode);
    }
    
    public static function isNumeric(?int $mode = null): Filter
    {
        return IsNumeric::create($mode);
    }
    
    public static function isString(?int $mode = null): Filter
    {
        return IsString::create($mode);
    }
    
    public static function isBool(?int $mode = null): Filter
    {
        return IsBool::create($mode);
    }
    
    public static function isFloat(?int $mode = null): Filter
    {
        return IsFloat::create($mode);
    }
    
    public static function isArray(?int $mode = null): Filter
    {
        return IsArray::create($mode);
    }
    
    /**
     * @param string|int $field
     * @param Filter|callable|mixed $filter
     */
    public static function filterBy($field, $filter): Filter
    {
        return FilterBy::create($field, $filter);
    }
    
    /**
     * It only passes array (or \ArrayAccess) values containing the specified field(s).
     * Currently, only VALUE mode is supported and attempting to change it will result in an exception.
     *
     * @param array<string|int>|string|int $fields
     */
    public static function onlyWith($fields, bool $allowNulls = false): Filter
    {
        return OnlyWith::create($fields, $allowNulls);
    }
    
    public static function contains(string $value, bool $ignoreCase = false): Filter
    {
        return self::string()->contains($value, $ignoreCase);
    }
    
    public static function startsWith(string $value, bool $ignoreCase = false): Filter
    {
        return self::string()->startsWith($value, $ignoreCase);
    }
    
    public static function endsWith(string $value, bool $ignoreCase = false): Filter
    {
        return self::string()->endsWith($value, $ignoreCase);
    }
    
    /**
     * @param Filter|callable|mixed ...$filters
     */
    public static function AND(...$filters): Filter
    {
        return BaseAND::create($filters);
    }
    
    /**
     * @param Filter|callable|mixed ...$filters
     */
    public static function OR(...$filters): Filter
    {
        return BaseOR::create($filters);
    }
    
    /**
     * @param Filter|callable|mixed $first
     * @param Filter|callable|mixed $second
     */
    public static function XOR($first, $second): Filter
    {
        return BaseXOR::create($first, $second);
    }
    
    /**
     * @param Filter|callable|mixed $first
     * @param Filter|callable|mixed $second
     */
    public static function XNOR($first, $second): Filter
    {
        return BaseXNOR::create($first, $second);
    }
    
    /**
     * @param Filter|callable|mixed $filter
     */
    public static function NOT($filter): Filter
    {
        return self::getAdapter($filter)->negate();
    }
    
    /**
     * @param string|int $field
     */
    public static function hasField($field): Filter
    {
        return self::getAdapter(static fn($row): bool => isset($row[$field]));
    }
}