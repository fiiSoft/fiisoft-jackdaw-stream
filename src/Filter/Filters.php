<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Filter\Adapter\SequencePredicateAdapter;
use FiiSoft\Jackdaw\Filter\Adjuster\UnwrapFilterAdjuster;
use FiiSoft\Jackdaw\Filter\CheckType\TypeFilterFactory;
use FiiSoft\Jackdaw\Filter\CheckType\TypeFilterPicker;
use FiiSoft\Jackdaw\Filter\Generic\GenericFilter;
use FiiSoft\Jackdaw\Filter\Logic\OpAND\BaseAND;
use FiiSoft\Jackdaw\Filter\Logic\OpOR\BaseOR;
use FiiSoft\Jackdaw\Filter\Logic\OpXNOR\BaseXNOR;
use FiiSoft\Jackdaw\Filter\Logic\OpXOR\BaseXOR;
use FiiSoft\Jackdaw\Filter\Number\NumberFilterFactory;
use FiiSoft\Jackdaw\Filter\Number\NumberFilterPicker;
use FiiSoft\Jackdaw\Filter\OnlyIn\OnlyIn;
use FiiSoft\Jackdaw\Filter\OnlyWith\OnlyWith;
use FiiSoft\Jackdaw\Filter\Simple\SimpleFilterFactory;
use FiiSoft\Jackdaw\Filter\Simple\SimpleFilterPicker;
use FiiSoft\Jackdaw\Filter\Size\Count\CountFilterFactory;
use FiiSoft\Jackdaw\Filter\Size\Count\CountFilterPicker;
use FiiSoft\Jackdaw\Filter\Size\Length\LengthFilterFactory;
use FiiSoft\Jackdaw\Filter\Size\Length\LengthFilterPicker;
use FiiSoft\Jackdaw\Filter\String\StringFilter;
use FiiSoft\Jackdaw\Filter\String\StringFilterFactory;
use FiiSoft\Jackdaw\Filter\String\StringFilterPicker;
use FiiSoft\Jackdaw\Filter\Time\TimeFilterFactory;
use FiiSoft\Jackdaw\Filter\Time\TimeFilterPicker;
use FiiSoft\Jackdaw\Filter\ValRef\Adapter\MemoReader\MemoFilterPicker;
use FiiSoft\Jackdaw\Filter\ValRef\Adapter\Reference\ReferenceFilterPicker;
use FiiSoft\Jackdaw\Filter\ValRef\FilterPicker;
use FiiSoft\Jackdaw\Filter\ValRef\Picker\Custom\IntValNumberFilterPicker;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Memo\MemoReader;
use FiiSoft\Jackdaw\Memo\SequencePredicate;
use FiiSoft\Jackdaw\ValueRef\IntValue;

final class Filters
{
    /**
     * @param FilterReady|callable|array<string|int, mixed>|scalar $filter
     */
    public static function getAdapter($filter, ?int $mode = null): Filter
    {
        if ($filter instanceof Filter) {
            return UnwrapFilterAdjuster::unwrap($filter)->inMode($mode);
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
        
        if ($filter instanceof SequencePredicate) {
            return new SequencePredicateAdapter($filter);
        }
        
        if (\is_object($filter)) {
            throw InvalidParamException::describe('filter', $filter);
        }
        
        return self::same($filter, $mode);
    }
    
    public static function time(?int $mode = null): TimeFilterPicker
    {
        return TimeFilterFactory::instance($mode);
    }
    
    public static function string(?int $mode = null): StringFilterPicker
    {
        return StringFilterFactory::instance($mode);
    }
    
    public static function size(?int $mode = null): CountFilterPicker
    {
        return CountFilterFactory::instance($mode);
    }
    
    public static function length(?int $mode = null): LengthFilterPicker
    {
        return LengthFilterFactory::instance($mode);
    }
    
    public static function number(?int $mode = null): NumberFilterPicker
    {
        return NumberFilterFactory::instance($mode);
    }
    
    public static function type(?int $mode = null): TypeFilterPicker
    {
        return TypeFilterFactory::instance($mode);
    }
    
    public static function isEmpty(?int $mode = null): Filter
    {
        return self::type($mode)->isEmpty();
    }
    
    public static function notEmpty(?int $mode = null): Filter
    {
        return self::type($mode)->notEmpty();
    }
    
    public static function isDateTime(?int $mode = null): Filter
    {
        return self::type($mode)->isDateTime();
    }
    
    public static function isCountable(?int $mode = null): Filter
    {
        return self::type($mode)->isCountable();
    }
    
    public static function isNull(?int $mode = null): Filter
    {
        return self::type($mode)->isNull();
    }
    
    public static function notNull(?int $mode = null): Filter
    {
        return self::type($mode)->notNull();
    }
    
    public static function isInt(?int $mode = null): Filter
    {
        return self::type($mode)->isInt();
    }
    
    public static function isNumeric(?int $mode = null): Filter
    {
        return self::type($mode)->isNumeric();
    }
    
    public static function isString(?int $mode = null): Filter
    {
        return self::type($mode)->isString();
    }
    
    public static function isBool(?int $mode = null): Filter
    {
        return self::type($mode)->isBool();
    }
    
    public static function isFloat(?int $mode = null): Filter
    {
        return self::type($mode)->isFloat();
    }
    
    public static function isArray(?int $mode = null): Filter
    {
        return self::type($mode)->isArray();
    }
    
    public static function contains(string $value, bool $ignoreCase = false): StringFilter
    {
        return self::string()->contains($value, $ignoreCase);
    }
    
    public static function startsWith(string $value, bool $ignoreCase = false): StringFilter
    {
        return self::string()->startsWith($value, $ignoreCase);
    }
    
    public static function endsWith(string $value, bool $ignoreCase = false): StringFilter
    {
        return self::string()->endsWith($value, $ignoreCase);
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
    
    private static function simple(?int $mode = null): SimpleFilterPicker
    {
        return SimpleFilterFactory::instance($mode);
    }
    
    /**
     * @param mixed $value
     */
    public static function equal($value, ?int $mode = null): Filter
    {
        return self::simple($mode)->equal($value);
    }
    
    /**
     * @param mixed $value
     */
    public static function notEqual($value, ?int $mode = null): Filter
    {
        return self::simple($mode)->notEqual($value);
    }
    
    /**
     * @param mixed $value
     */
    public static function same($value, ?int $mode = null): Filter
    {
        return $value === null
            ? self::isNull($mode)
            : self::simple($mode)->same($value);
    }
    
    /**
     * @param mixed $value
     */
    public static function notSame($value, ?int $mode = null): Filter
    {
        return self::simple($mode)->notSame($value);
    }
    
    /**
     * @param array<string|int, mixed> $values
     */
    public static function onlyIn(array $values, ?int $mode = null): Filter
    {
        return OnlyIn::create($mode, $values);
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
    
    /**
     * @param string|int $field
     * @param FilterReady|callable|array<string|int, mixed>|scalar $filter
     */
    public static function filterBy($field, $filter): Filter
    {
        return new FilterBy($field, $filter);
    }
    
    /**
     * @param FilterReady|callable|array<string|int, mixed>|scalar ...$filters
     */
    public static function AND(...$filters): Filter
    {
        return BaseAND::create($filters);
    }
    
    /**
     * @param FilterReady|callable|array<string|int, mixed>|scalar ...$filters
     */
    public static function OR(...$filters): Filter
    {
        return BaseOR::create($filters);
    }
    
    /**
     * @param FilterReady|callable|array<string|int, mixed>|scalar $first
     * @param FilterReady|callable|array<string|int, mixed>|scalar $second
     */
    public static function XOR($first, $second): Filter
    {
        return BaseXOR::create($first, $second);
    }
    
    /**
     * @param FilterReady|callable|array<string|int, mixed>|scalar $first
     * @param FilterReady|callable|array<string|int, mixed>|scalar $second
     */
    public static function XNOR($first, $second): Filter
    {
        return BaseXNOR::create($first, $second);
    }
    
    /**
     * @param FilterReady|callable|array<string|int, mixed>|scalar $filter
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
    
    /**
     * @param array<mixed>|object|scalar|null $var REFERENCE
     */
    public static function readFrom(&$var): FilterPicker
    {
        return new ReferenceFilterPicker($var);
    }
    
    public static function wrapIntValue(IntValue $value): NumberFilterPicker
    {
        return new IntValNumberFilterPicker($value);
    }
    
    public static function wrapMemoReader(MemoReader $memo): FilterPicker
    {
        return new MemoFilterPicker($memo);
    }
    
    /**
     * Currently, only values are filtered by this filter.
     */
    public static function byArgs(callable $filter): Filter
    {
        return new ByArgs($filter);
    }
    
    /**
     * @param mixed $desired
     */
    public static function keyIs($desired): Filter
    {
        return self::same($desired, Check::KEY);
    }
}