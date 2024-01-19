<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper\Internal;

use FiiSoft\Jackdaw\Mapper\Mapper;

trait SimpleCastMapper
{
    final public function isStateless(): bool
    {
        return true;
    }
    
    final public function mergeWith(Mapper $other): bool
    {
        return $other instanceof $this;
    }
}