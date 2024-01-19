<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Condition;

use FiiSoft\Jackdaw\Condition\Adapter\FilterAdapter;
use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Filter\Filter;

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
    
        throw InvalidParamException::describe('condition', $condition);
    }
    
    /**
     * @param string|int $condition
     */
    public static function keyEquals($condition): Condition
    {
        return self::getAdapter(static fn($_, $key): bool => $key === $condition);
    }
}