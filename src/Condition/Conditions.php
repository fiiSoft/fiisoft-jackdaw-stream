<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Condition;

use FiiSoft\Jackdaw\Condition\Adapter\FilterAdapter;
use FiiSoft\Jackdaw\Condition\Adapter\PredicateAdapter;
use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Predicate\Predicate;

final class Conditions
{
    /**
     * @param Condition|Predicate|Filter|callable $condition
     * @return Condition
     */
    public static function getAdapter($condition): Condition
    {
        if ($condition instanceof Condition) {
            return $condition;
        }
    
        if (\is_callable($condition)) {
            return self::generic($condition);
        }
    
        if ($condition instanceof Filter) {
            return self::filter($condition);
        }
    
        if ($condition instanceof Predicate) {
            return self::predicate($condition);
        }
        
        throw new \InvalidArgumentException('Invalid param condition');
    }
    
    public static function generic(callable $condition): GenericCondition
    {
        return new GenericCondition($condition);
    }
    
    public static function filter(Filter $filter): FilterAdapter
    {
        return new FilterAdapter($filter);
    }
    
    public static function predicate(Predicate $predicate): PredicateAdapter
    {
        return new PredicateAdapter($predicate);
    }
}