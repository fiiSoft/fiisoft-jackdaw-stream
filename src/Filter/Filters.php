<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter;

use FiiSoft\Jackdaw\Filter\Adapter\PredicateAdapter;
use FiiSoft\Jackdaw\Filter\Internal\LengthFactory;
use FiiSoft\Jackdaw\Filter\Internal\NumberFactory;
use FiiSoft\Jackdaw\Filter\Internal\StringFactory;
use FiiSoft\Jackdaw\Filter\Logic\FilterAND;
use FiiSoft\Jackdaw\Filter\Logic\FilterNOT;
use FiiSoft\Jackdaw\Filter\Logic\FilterOR;
use FiiSoft\Jackdaw\Filter\Logic\FilterXOR;
use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Predicate\Predicate;

final class Filters
{
    /**
     * @param Filter|Predicate|callable|mixed $filter
     */
    public static function getAdapter($filter): Filter
    {
        if ($filter instanceof Filter) {
            return $filter;
        }
    
        if (\is_callable($filter)) {
            if (\is_string($filter)) {
                switch ($filter) {
                    case 'is_int':
                    case '\is_int':
                        return self::isInt();
                    case 'is_numeric':
                    case '\is_numeric':
                        return self::isNumeric();
                    case 'is_string':
                    case '\is_string':
                        return self::isString();
                    case 'is_float':
                    case '\is_float':
                        return self::isFloat();
                    case 'is_null':
                    case '\is_null':
                        return self::isNull();
                    case 'is_bool':
                    case '\is_bool':
                        return self::isBool();
                    default:
                        //noop
                }
            }
            
            return self::generic($filter);
        }
    
        if (\is_object($filter)) {
            if ($filter instanceof Predicate) {
                return new PredicateAdapter($filter);
            }
            
            throw Helper::invalidParamException('filter', $filter);
        }
        
        return self::same($filter);
    }
    
    public static function generic(callable $filter): Filter
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
    
    public static function notEmpty(): Filter
    {
        return new NotEmpty();
    }
    
    public static function notNull(): Filter
    {
        return new NotNull();
    }
    
    public static function isNull(): Filter
    {
        return new IsNull();
    }
    
    public static function onlyIn(array $values): Filter
    {
        return new OnlyIn($values);
    }
    
    /**
     * @param mixed $value
     */
    public static function same($value): Filter
    {
        return new Same($value);
    }
    
    /**
     * @param float|int $value
     */
    public static function greaterThan($value): Filter
    {
        return self::number()->gt($value);
    }
    
    /**
     * @param float|int $value
     */
    public static function greaterOrEqual($value): Filter
    {
        return self::number()->ge($value);
    }
    
    /**
     * @param float|int $value
     */
    public static function lessThan($value): Filter
    {
        return self::number()->lt($value);
    }
    
    /**
     * @param float|int $value
     */
    public static function lessOrEqual($value): Filter
    {
        return self::number()->le($value);
    }
    
    public static function isInt(): Filter
    {
        return new IsInt();
    }
    
    public static function isNumeric(): Filter
    {
        return new IsNumeric();
    }
    
    public static function isString(): Filter
    {
        return new IsString();
    }
    
    public static function isBool(): Filter
    {
        return new IsBool();
    }
    
    public static function isFloat(): Filter
    {
        return new IsFloat();
    }
    
    /**
     * @param string|int $field
     * @param Filter|Predicate|callable|mixed $filter
     */
    public static function filterBy($field, $filter): Filter
    {
        return new FilterBy($field, self::getAdapter($filter));
    }
    
    /**
     * @param array|string|int $keys
     */
    public static function onlyWith($keys, bool $allowNulls = false): Filter
    {
        return new OnlyWith($keys, $allowNulls);
    }
    
    public static function string(): StringFactory
    {
        return StringFactory::instance();
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
     * @param Filter|Predicate|callable|mixed ...$filters
     */
    public static function AND(...$filters): Filter
    {
        return new FilterAND($filters);
    }
    
    /**
     * @param Filter|Predicate|callable|mixed ...$filters
     */
    public static function OR(...$filters): Filter
    {
        return new FilterOR($filters);
    }
    
    /**
     * @param Filter|Predicate|callable|mixed $first
     * @param Filter|Predicate|callable|mixed $second
     */
    public static function XOR($first, $second): Filter
    {
        return new FilterXOR($first, $second);
    }
    
    /**
     * @param Filter|Predicate|callable|mixed $filter
     */
    public static function NOT($filter): Filter
    {
        return new FilterNOT($filter);
    }
    
    /**
     * @param string|int $field
     */
    public static function hasField($field): Filter
    {
        return self::generic(static fn($row): bool => \is_array($row) && isset($row[$field]));
    }
}