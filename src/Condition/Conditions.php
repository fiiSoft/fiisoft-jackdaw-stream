<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Condition;

use FiiSoft\Jackdaw\Condition\Adapter\FilterAdapter;
use FiiSoft\Jackdaw\Condition\Adapter\SequencePredicateAdapter;
use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Memo\SequencePredicate;

final class Conditions
{
    /**
     * @param ConditionReady|callable $condition
     */
    public static function getAdapter($condition, ?int $mode = null): Condition
    {
        if ($condition instanceof Condition) {
            return $condition;
        }
    
        if (\is_callable($condition)) {
            return GenericCondition::create($condition);
        }
    
        if ($condition instanceof Filter) {
            return new FilterAdapter($condition, $mode);
        }
        
        if ($condition instanceof SequencePredicate) {
            return new SequencePredicateAdapter($condition);
        }
    
        throw InvalidParamException::describe('condition', $condition);
    }
    
    /**
     * @deprecated the name of this method is very unfortunate
     * @param string|int $condition this name is bad too
     */
    public static function keyEquals($condition): Condition
    {
        return self::keyIs($condition);
    }
    
    /**
     * @param string|int $desired
     */
    public static function keyIs($desired): Condition
    {
        return self::getAdapter(static fn($_, $key): bool => $key === $desired);
    }
}