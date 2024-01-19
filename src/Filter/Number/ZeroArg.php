<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Number;

use FiiSoft\Jackdaw\Filter\Filter;

abstract class ZeroArg extends NumberFilter
{
    abstract protected static function create(int $mode): self;
    
    final public function inMode(?int $mode): Filter
    {
        return $mode !== null && $mode !== $this->mode
            ? static::create($mode)
            : $this;
    }
}