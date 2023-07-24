<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Condition;

use FiiSoft\Jackdaw\Condition\Adapter\FilterAdapter;
use FiiSoft\Jackdaw\Condition\Adapter\PredicateAdapter;
use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Predicate\Predicate;

final class Conditions
{
    /**
     * @param Condition|Predicate|Filter|callable $condition
     */
    public static function getAdapter($condition, int $mode = Check::VALUE): Condition
    {
        if ($condition instanceof Condition) {
            return $condition;
        }
    
        if (\is_callable($condition)) {
            return self::generic($condition);
        }
    
        if ($condition instanceof Filter) {
            return self::filter($condition, $mode);
        }
    
        if ($condition instanceof Predicate) {
            return self::predicate($condition, $mode);
        }
        
        throw new \InvalidArgumentException('Invalid param condition');
    }
    
    public static function generic(callable $condition): Condition
    {
        return new GenericCondition($condition);
    }
    
    public static function filter(Filter $filter, int $mode = Check::VALUE): Condition
    {
        return new FilterAdapter($filter, $mode);
    }
    
    public static function predicate(Predicate $predicate, int $mode = Check::VALUE): Condition
    {
        return new PredicateAdapter($predicate, $mode);
    }
    
    /**
     * @param string|int $condition
     */
    public static function keyEquals($condition): Condition
    {
        return self::generic(static function ($_, $key) use ($condition) {
            return $key === $condition;
        });
    }
}