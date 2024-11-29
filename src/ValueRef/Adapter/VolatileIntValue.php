<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\ValueRef\Adapter;

use FiiSoft\Jackdaw\ValueRef\IntValue;

abstract class VolatileIntValue implements IntValue
{
    final public function isConstant(): bool
    {
        return false;
    }
}