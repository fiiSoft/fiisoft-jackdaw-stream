<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\CheckType;

use FiiSoft\Jackdaw\Filter\BaseFilter;
use FiiSoft\Jackdaw\Filter\Filter;

abstract class CheckType extends BaseFilter
{
    abstract protected static function create(int $mode): self;
    
    final public function inMode(?int $mode): Filter
    {
        return $mode !== null && $mode !== $this->mode
            ? static::create($mode)
            : $this;
    }
    
    public function negate(): Filter
    {
        return $this->createDefaultNOT(true);
    }
}