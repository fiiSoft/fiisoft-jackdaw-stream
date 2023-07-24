<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Reducer;

final class Reducers
{
    /**
     * @param Reducer|callable $reducer
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
    
        throw new \InvalidArgumentException('Invalid param reducer');
    }
    
    public static function generic(callable $reducer): Reducer
    {
        return new GenericReducer($reducer);
    }
    
    public static function sum(): Reducer
    {
        return new Sum();
    }
    
    public static function min(): Reducer
    {
        return new Min();
    }
    
    public static function max(): Reducer
    {
        return new Max();
    }
    
    public static function average(?int $roundPrecision = null): Reducer
    {
        return new Average($roundPrecision);
    }
    
    public static function concat(string $separator = ''): Reducer
    {
        return new Concat($separator);
    }
    
    public static function longest(): Reducer
    {
        return new Longest();
    }
    
    public static function shortest(): Reducer
    {
        return new Shortest();
    }
    
    public static function count(): Reducer
    {
        return new Count();
    }
}