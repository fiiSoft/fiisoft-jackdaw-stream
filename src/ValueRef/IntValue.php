<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\ValueRef;

interface IntValue extends IntProvider
{
    public function int(): int;
    
    /**
     * The integer value returned by the int() method is considered constant only
     * if it is known before the stream iteration starts and does not change during iteration.
     */
    public function isConstant(): bool;
    
    public function equals(IntValue $other): bool;
}