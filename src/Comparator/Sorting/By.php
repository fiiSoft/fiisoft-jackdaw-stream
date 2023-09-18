<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator\Sorting;

use FiiSoft\Jackdaw\Comparator\Comparator;
use FiiSoft\Jackdaw\Comparator\Comparators;
use FiiSoft\Jackdaw\Internal\Check;

final class By
{
    /**
     * @param Comparator|callable|null $comparator
     */
    public static function value($comparator = null, bool $reversed = false): Sorting
    {
        return Value::from($reversed, $comparator);
    }
    
    /**
     * @param Comparator|callable|null $comparator
     */
    public static function valueAsc($comparator = null): Sorting
    {
        return self::value($comparator, false);
    }
    
    /**
     * @param Comparator|callable|null $comparator
     */
    public static function valueDesc($comparator = null): Sorting
    {
        return self::value($comparator, true);
    }
    
    /**
     * @param Comparator|callable|null $comparator
     */
    public static function key($comparator = null, bool $reversed = false): Sorting
    {
        return Key::from($reversed, $comparator);
    }
    
    /**
     * @param Comparator|callable|null $comparator
     */
    public static function keyAsc($comparator = null): Sorting
    {
        return self::key($comparator, false);
    }
    
    /**
     * @param Comparator|callable|null $comparator
     */
    public static function keyDesc($comparator = null): Sorting
    {
        return self::key($comparator, true);
    }
    
    /**
     * @param Comparator|callable|null $comparator
     */
    public static function assoc($comparator = null, bool $reversed = false): Sorting
    {
        return Sorting::create($reversed, $comparator, Check::BOTH);
    }
    
    /**
     * @param Comparator|callable|null $comparator
     */
    public static function assocAsc($comparator = null): Sorting
    {
        return self::assoc($comparator, false);
    }
    
    /**
     * @param Comparator|callable|null $comparator
     */
    public static function assocDesc($comparator = null): Sorting
    {
        return self::assoc($comparator, true);
    }
    
    public static function both(Sorting $first, Sorting $second): Sorting
    {
        return Sorting::double($first, $second);
    }
    
    /**
     * @param Comparator|callable|null $comparator
     */
    public static function bothAsc($comparator = null): Sorting
    {
        return self::both(Value::asc($comparator), Key::asc($comparator));
    }
    
    /**
     * @param Comparator|callable|null $comparator
     */
    public static function bothDesc($comparator = null): Sorting
    {
        return self::both(Value::desc($comparator), Key::desc($comparator));
    }
    
    /**
     * @param array<string|int> $fields
     */
    public static function fields(array $fields, bool $reversed = false): Sorting
    {
        return self::value(Comparators::fields($fields), $reversed);
    }
    
    /**
     * @param array<string|int> $fields
     */
    public static function fieldsAsc(array $fields): Sorting
    {
        return self::fields($fields);
    }
    
    /**
     * @param array<string|int> $fields
     */
    public static function fieldsDesc(array $fields): Sorting
    {
        return self::fields($fields, true);
    }
    
    public static function size(bool $reversed = false): Sorting
    {
        return self::value(Comparators::size(), $reversed);
    }
    
    public static function sizeAsc(): Sorting
    {
        return self::size();
    }
    
    public static function sizeDesc(): Sorting
    {
        return self::size(true);
    }
}