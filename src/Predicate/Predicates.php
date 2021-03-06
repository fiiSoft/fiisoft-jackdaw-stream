<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Predicate;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Predicate\Adapter\FilterAdapter;

final class Predicates
{
    /**
     * @param Predicate|Filter|callable|mixed $predicate
     * @return Predicate
     */
    public static function getAdapter($predicate): Predicate
    {
        if (\is_array($predicate)) {
            $adapter = self::inArray($predicate);
        } elseif ($predicate instanceof Predicate) {
            $adapter = $predicate;
        } elseif (\is_callable($predicate)) {
            $adapter = self::generic($predicate);
        } elseif ($predicate instanceof Filter) {
            $adapter = self::filter($predicate);
        } else {
            $adapter = self::value($predicate);
        }
        
        return $adapter;
    }
    
    public static function generic(callable $predicate): GenericPredicate
    {
        return new GenericPredicate($predicate);
    }
    
    public static function value($value): Value
    {
        return new Value($value);
    }
    
    public static function inArray(array $values): InArray
    {
        return new InArray($values);
    }
    
    public static function filter(Filter $filter): FilterAdapter
    {
        return new FilterAdapter($filter);
    }
}