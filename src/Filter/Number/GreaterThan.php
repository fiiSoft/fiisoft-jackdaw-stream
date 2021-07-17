<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Number;

final class GreaterThan extends NumberFilter
{
    protected function test($value): bool
    {
        if (\is_int($value) || \is_float($value)) {
            return $value > $this->value;
        }
    
        if (\is_numeric($value)) {
            return (float) $value > (float) $this->value;
        }
        
        throw new \LogicException('Cannot compare value which is not a number');
    }
}