<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Generic;

use FiiSoft\Jackdaw\Filter\Filter;

abstract class ModeIndependent extends GenericFilter
{
    final public function inMode(?int $mode): Filter
    {
        return $this;
    }
    
    final public function negate(): Filter
    {
        return self::create($this->callable, null, $this->expected);
    }
}