<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator;

use FiiSoft\Jackdaw\Comparator\Adapter\DiscriminatorAdapter;
use FiiSoft\Jackdaw\Comparator\Adapter\FilterAdapter;
use FiiSoft\Jackdaw\Comparator\Basic\DefaultComparator;
use FiiSoft\Jackdaw\Comparator\Basic\FieldsComparator;
use FiiSoft\Jackdaw\Comparator\Basic\GenericComparator;
use FiiSoft\Jackdaw\Comparator\Basic\LengthComparator;
use FiiSoft\Jackdaw\Comparator\Basic\MultiComparator;
use FiiSoft\Jackdaw\Comparator\Basic\ReverseComparator;
use FiiSoft\Jackdaw\Comparator\Basic\SizeComparator;
use FiiSoft\Jackdaw\Discriminator\Discriminator;
use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Filter\Filter;

final class Comparators
{
    /**
     * @param ComparatorReady|callable|null $comparator
     */
    public static function prepare($comparator): Comparator
    {
        return self::getAdapter($comparator) ?? self::default();
    }
    
    /**
     * @param ComparatorReady|callable|null $comparator
     */
    public static function getAdapter($comparator): ?Comparator
    {
        if ($comparator === null) {
            return null;
        }
        
        if ($comparator instanceof Comparator) {
            return $comparator;
        }
        
        if ($comparator instanceof ComparisonSpec) {
            return $comparator->comparator();
        }
        
        if ($comparator instanceof Discriminator) {
            return new DiscriminatorAdapter($comparator);
        }
        
        if ($comparator instanceof Filter) {
            return new FilterAdapter($comparator);
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
     * @param ComparatorReady|callable $comparators
     */
    public static function multi(...$comparators): MultiComparator
    {
        return new MultiComparator(...$comparators);
    }
}