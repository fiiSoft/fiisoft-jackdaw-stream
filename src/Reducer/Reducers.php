<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Reducer;

final class Reducers
{
    /**
     * @param Reducer|callable $reducer
     * @return Reducer
     */
    public static function getAdapter($reducer): Reducer
    {
        if ($reducer instanceof Reducer) {
            return $reducer;
        }
    
        if (\is_callable($reducer)) {
            if (\is_string($reducer)) {
                switch ($reducer) {
                    case 'min': return self::min();
                    case 'max': return self::max();
                    case 'array_sum': return self::sum();
                    default:
                        //noop
                }
            }
            
            return self::generic($reducer);
        }
    
        throw new \InvalidArgumentException('Invalid param reducer');
    }
    
    public static function generic(callable $reducer): GenericReducer
    {
        return new GenericReducer($reducer);
    }
    
    public static function sum(): Sum
    {
        return new Sum();
    }
    
    public static function min(): Min
    {
        return new Min();
    }
    
    public static function max(): Max
    {
        return new Max();
    }
    
    public static function average(?int $roundPrecision = null): Average
    {
        return new Average($roundPrecision);
    }
    
    public static function concat(string $separator = ''): Concat
    {
        return new Concat($separator);
    }
    
    public static function longest(): Longest
    {
        return new Longest();
    }
    
    public static function shortest(): Shortest
    {
        return new Shortest();
    }
}