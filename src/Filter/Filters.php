<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter;

use FiiSoft\Jackdaw\Filter\Internal\LengthFactory;
use FiiSoft\Jackdaw\Filter\Internal\NumberFactory;
use FiiSoft\Jackdaw\Filter\Number\GreaterOrEqual;
use FiiSoft\Jackdaw\Filter\Number\GreaterThan;
use FiiSoft\Jackdaw\Filter\Number\LessOrEqual;
use FiiSoft\Jackdaw\Filter\Number\LessThan;
use FiiSoft\Jackdaw\Internal\Helper;

final class Filters
{
    /**
     * @param Filter|callable|mixed $filter
     * @return Filter
     */
    public static function getAdapter($filter): Filter
    {
        if ($filter instanceof Filter) {
            return $filter;
        }
    
        if (\is_callable($filter)) {
            if (\is_string($filter)) {
                switch ($filter) {
                    case 'is_int': return self::isInt();
                    case 'is_numeric': return self::isNumeric();
                    case 'is_string': return self::isString();
                    default:
                        //noop
                }
            }
            
            return self::generic($filter);
        }
    
        if (\is_object($filter)) {
            throw Helper::invalidParamException('filter', $filter);
        }
        
        return self::equal($filter);
    }
    
    public static function generic(callable $filter): GenericFilter
    {
        return new GenericFilter($filter);
    }
    
    public static function length(): LengthFactory
    {
        return LengthFactory::instance();
    }
    
    public static function number(): NumberFactory
    {
        return NumberFactory::instance();
    }
    
    public static function notEmpty(): NotEmpty
    {
        return new NotEmpty();
    }
    
    public static function notNull(): NotNull
    {
        return new NotNull();
    }
    
    public static function onlyIn(array $values): OnlyIn
    {
        return new OnlyIn($values);
    }
    
    public static function equal($value): Filter
    {
        return new Equal($value);
    }
    
    /**
     * @param float|int $value
     * @return GreaterThan
     */
    public static function greaterThan($value): GreaterThan
    {
        return new GreaterThan($value);
    }
    
    /**
     * @param float|int $value
     * @return GreaterOrEqual
     */
    public static function greaterOrEqual($value): GreaterOrEqual
    {
        return new GreaterOrEqual($value);
    }
    
    /**
     * @param float|int $value
     * @return LessThan
     */
    public static function lessThan($value): LessThan
    {
        return new LessThan($value);
    }
    
    /**
     * @param float|int $value
     * @return LessOrEqual
     */
    public static function lessOrEqual($value): LessOrEqual
    {
        return new LessOrEqual($value);
    }
    
    public static function isInt(): IsInt
    {
        return new IsInt();
    }
    
    public static function isNumeric(): IsNumeric
    {
        return new IsNumeric();
    }
    
    public static function isString(): IsString
    {
        return new IsString();
    }
    
    /**
     * @param string|int $field
     * @param Filter|callable|mixed $filter
     * @return FilterBy
     */
    public static function filterBy($field, $filter): FilterBy
    {
        return new FilterBy($field, self::getAdapter($filter));
    }
    
    /**
     * @param array|string|int $keys
     * @param bool $allowNulls
     * @return OnlyWith
     */
    public static function onlyWith($keys, bool $allowNulls = false): OnlyWith
    {
        return new OnlyWith($keys, $allowNulls);
    }
}