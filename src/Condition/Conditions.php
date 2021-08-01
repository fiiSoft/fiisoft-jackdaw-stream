<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Condition;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Predicate\Predicate;

final class Conditions
{
    /**
     * @param Predicate|Filter|callable $condition
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
            return self::filterAdapter($condition);
        }
    
        if ($condition instanceof Predicate) {
            return self::predicateAdapter($condition);
        }
        
        throw new \InvalidArgumentException('Invalid param condition');
    }
    
    public static function generic(callable $condition): GenericCondition
    {
        return new GenericCondition($condition);
    }
    
    public static function filterAdapter(Filter $filter): FilterAdapter
    {
        return new FilterAdapter($filter);
    }
    
    public static function predicateAdapter(Predicate $predicate): PredicateAdapter
    {
        return new PredicateAdapter($predicate);
    }
}