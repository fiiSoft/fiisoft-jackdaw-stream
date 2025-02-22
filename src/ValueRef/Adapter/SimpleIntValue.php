<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\ValueRef\Adapter;

use FiiSoft\Jackdaw\ValueRef\IntValue;

final class SimpleIntValue implements IntValue
{
    private int $value;
    
    public function __construct(int $value)
    {
        $this->value = $value;
    }
    
    public function int(): int
    {
        return $this->value;
    }
    
    public function isConstant(): bool
    {
        return true;
    }
    
    public function equals(IntValue $other): bool
    {
        return $other instanceof $this && $other->value === $this->value;
    }
}