<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\ValueRef;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Registry\RegReader;
use FiiSoft\Jackdaw\ValueRef\Adapter\CompoundIntValue;
use FiiSoft\Jackdaw\ValueRef\Adapter\GenericIntValue;
use FiiSoft\Jackdaw\ValueRef\Adapter\ConsecutiveIntValue;
use FiiSoft\Jackdaw\ValueRef\Adapter\ReferenceToInt;
use FiiSoft\Jackdaw\ValueRef\Adapter\RegistryIntValue;
use FiiSoft\Jackdaw\ValueRef\Adapter\SimpleIntValue;

final class IntNum
{
    /**
     * @param IntProvider|iterable<int>|callable|int $value
     */
    public static function getAdapter($value): IntValue
    {
        if (\is_int($value)) {
            return self::constant($value);
        }
        
        if ($value instanceof IntValue) {
            return $value;
        }
        
        if ($value instanceof RegReader) {
            return new RegistryIntValue($value);
        }
        
        if (\is_iterable($value)) {
            return self::consecutive($value);
        }
        
        if (\is_callable($value)) {
            return new GenericIntValue($value);
        }
        
        throw InvalidParamException::describe('value', $value);
    }
    
    public static function addArgs(IntValue $first, IntValue $second): IntValue
    {
        return $first->isConstant() && $second->isConstant()
            ? self::constant($first->int() + $second->int())
            : new CompoundIntValue($first, $second);
    }
    
    public static function constant(int $value): IntValue
    {
        return new SimpleIntValue($value);
    }
    
    /**
     * @param int|null $variable REFERENCE is set to 0 when NULL during initialization
     */
    public static function readFrom(?int &$variable): IntValue
    {
        return new ReferenceToInt($variable);
    }
    
    /**
     * @param iterable<int> $values
     */
    public static function infinitely(iterable $values): IntValue
    {
        return self::consecutive($values, true);
    }
    
    /**
     * @param iterable<int> $values
     */
    public static function consecutive(iterable $values, bool $infinite = false): IntValue
    {
        return new ConsecutiveIntValue($values, $infinite);
    }
}