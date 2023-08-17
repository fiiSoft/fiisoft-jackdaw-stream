<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Predicate;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Predicate\Adapter\FilterAdapter;

final class Predicates
{
    /**
     * @param Predicate|Filter|callable|mixed $predicate
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
    
    public static function generic(callable $predicate): Predicate
    {
        return new GenericPredicate($predicate);
    }
    
    /**
     * @param mixed $value
     */
    public static function value($value): Predicate
    {
        return new Value($value);
    }
    
    public static function inArray(array $values): Predicate
    {
        return new InArray($values);
    }
    
    public static function filter(Filter $filter): Predicate
    {
        return new FilterAdapter($filter);
    }
}