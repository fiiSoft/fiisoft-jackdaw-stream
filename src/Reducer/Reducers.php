<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Reducer;

use FiiSoft\Jackdaw\Discriminator\DiscriminatorReady;
use FiiSoft\Jackdaw\Exception\InvalidParamException;

final class Reducers
{
    /**
     * @param Reducer|callable|array<Reducer|callable> $reducer
     *        callable must accepts two arguments: accumulator and current value
     */
    public static function getAdapter($reducer): Reducer
    {
        if ($reducer instanceof Reducer) {
            return $reducer;
        }
    
        if (\is_callable($reducer)) {
            if (\is_string($reducer)) {
                switch ($reducer) {
                    case 'min':
                    case '\min':
                        return self::min();
                    case 'max':
                    case '\max':
                        return self::max();
                    case 'array_sum':
                    case '\array_sum':
                        return self::sum();
                    case 'implode':
                    case '\implode':
                        return self::concat();
                    case 'count':
                    case '\count':
                        return self::count();
                    default:
                        //noop
                }
            }
            
            return self::generic($reducer);
        }
        
        if (\is_array($reducer)) {
            return new MultiReducer($reducer);
        }
    
        throw InvalidParamException::describe('reducer', $reducer);
    }
    
    /**
     * @param callable $reducer it should accept two arguments: accumulator and current value,
     *                          and must return new accumulator
     */
    public static function generic(callable $reducer): Reducer
    {
        return new GenericReducer($reducer);
    }
    
    /**
     * Reduce to sum of numbers.
     */
    public static function sum(): Reducer
    {
        return new Sum();
    }
    
    /**
     * Reduce to product of numbers.
     */
    public static function product(): Reducer
    {
        return new Product();
    }
    
    /**
     * Reduce to min number.
     */
    public static function min(): Reducer
    {
        return new Min();
    }
    
    /**
     * Reduce to max number.
     */
    public static function max(): Reducer
    {
        return new Max();
    }
    
    /**
     * Reduce to array with min and max values of numbers.
     */
    public static function minMax(): Reducer
    {
        return new MinMax();
    }
    
    /**
     * Compute average value of numbers.
     */
    public static function average(?int $roundPrecision = null): Reducer
    {
        return new Average($roundPrecision);
    }
    
    /**
     * Reduce to array with min, max, count, sum and average of numbers.
     */
    public static function basicStats(?int $roundPrecision = null): Reducer
    {
        return new BasicStats($roundPrecision);
    }
    
    /**
     * Concat operation for string values.
     */
    public static function concat(string $separator = ''): Reducer
    {
        return new Concat($separator);
    }
    
    /**
     * It operates on strings and returns the longest string.
     */
    public static function longest(): Reducer
    {
        return new Longest();
    }
    
    /**
     * It operates on strings and returns the shortest string.
     */
    public static function shortest(): Reducer
    {
        return new Shortest();
    }
    
    /**
     * It operates on \Countable values and arrays and returns the largest one.
     */
    public static function largest(): Reducer
    {
        return new Largest();
    }
    
    /**
     * It operates on \Countable values and arrays and returns the smallest one.
     */
    public static function smallest(): Reducer
    {
        return new Smallest();
    }
    
    /**
     * Counts elements.
     */
    public static function count(): Reducer
    {
        return new Count();
    }
    
    /**
     * Counts unique elements.
     *
     * @param DiscriminatorReady|callable|array<string|int>|null $discriminator
     */
    public static function countUnique($discriminator = null): Reducer
    {
        return new CountUnique($discriminator);
    }
}