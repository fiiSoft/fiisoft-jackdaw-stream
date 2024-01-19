<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator;

use FiiSoft\Jackdaw\Comparator\Basic\DefaultComparator;
use FiiSoft\Jackdaw\Comparator\Basic\FieldsComparator;
use FiiSoft\Jackdaw\Comparator\Basic\GenericComparator;
use FiiSoft\Jackdaw\Comparator\Basic\LengthComparator;
use FiiSoft\Jackdaw\Comparator\Basic\MultiComparator;
use FiiSoft\Jackdaw\Comparator\Basic\ReverseComparator;
use FiiSoft\Jackdaw\Comparator\Basic\SizeComparator;
use FiiSoft\Jackdaw\Exception\InvalidParamException;

final class Comparators
{
    /**
     * @param Comparable|callable|null $comparator
     */
    public static function prepare($comparator): Comparator
    {
        return self::getAdapter($comparator) ?? self::default();
    }
    
    /**
     * @param Comparable|callable|null $comparator
     */
    public static function getAdapter($comparator): ?Comparator
    {
        if ($comparator === null) {
            return null;
        }
        
        if ($comparator instanceof Comparator) {
            return $comparator;
        }
        
        if ($comparator instanceof Comparable) {
            return $comparator->comparator();
        }
        
        if (\is_callable($comparator)) {
            return GenericComparator::create($comparator);
        }
        
        throw InvalidParamException::describe('comparator', $comparator);
    }
    
    public static function default(): Comparator
    {
        return new DefaultComparator();
    }
    
    public static function reverse(): Comparator
    {
        return new ReverseComparator();
    }
    
    /**
     * @param array<string|int> $fields
     */
    public static function fields(array $fields): Comparator
    {
        return new FieldsComparator($fields);
    }
    
    /**
     * Allows to compare size of arrays or \Countable objects.
     */
    public static function size(): Comparator
    {
        return new SizeComparator();
    }
    
    /**
     * Allows to compare length of strings.
     */
    public static function length(): Comparator
    {
        return new LengthComparator();
    }
    
    /**
     * @param Comparator|callable $comparators
     */
    public static function multi(...$comparators): MultiComparator
    {
        return new MultiComparator(...$comparators);
    }
}