<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\ValueRef\Adapter;

use FiiSoft\Jackdaw\ValueRef\IntValue;
use FiiSoft\Jackdaw\ValueRef\IntNum;

final class CompoundIntValue implements IntValue
{
    /** @var array<int, IntValue> */
    private array $values = [];
    
    public function __construct(IntValue ...$values)
    {
        $constant = null;
        
        foreach ($this->unwrap($values) as $value) {
            if ($value->isConstant()) {
                if ($constant === null) {
                    $constant = $value;
                } else {
                    $constant = IntNum::constant($constant->int() + $value->int());
                }
            } else {
                $this->values[] = $value;
            }
        }
        
        if ($constant !== null) {
            $this->values[] = $constant;
        }
    }
    
    /**
     * @param IntValue[] $values
     * @return IntValue[]
     */
    private function unwrap(array $values): array
    {
        $flat = [];
        
        foreach ($values as $value) {
            if ($value instanceof self) {
                /** @psalm-suppress DuplicateArrayKey */
                $flat = [...$flat, ...$value->values];
            } else {
                $flat[] = $value;
            }
        }
        
        return $flat;
    }
    
    public function int(): int
    {
        $total = 0;
        
        foreach ($this->values as $value) {
            $total += $value->int();
        }
        
        return $total;
    }
    
    public function isConstant(): bool
    {
        foreach ($this->values as $value) {
            if (!$value->isConstant()) {
                return false;
            }
        }
        
        return true;
    }
}