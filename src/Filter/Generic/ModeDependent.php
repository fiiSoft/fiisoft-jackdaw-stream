<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Generic;

use FiiSoft\Jackdaw\Filter\Filter;

abstract class ModeDependent extends GenericFilter
{
    final public function inMode(?int $mode): Filter
    {
        return $mode !== null && $mode !== $this->mode
            ? self::create($this->callable, $mode, !$this->expected)
            : $this;
    }
    
    final public function negate(): Filter
    {
        return self::create($this->callable, $this->negatedMode(), $this->expected);
    }
}