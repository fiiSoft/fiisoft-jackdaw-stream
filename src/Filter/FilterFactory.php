<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter;

use FiiSoft\Jackdaw\Internal\Mode;

abstract class FilterFactory
{
    protected int $mode;
    
    protected bool $isNot;
    
    final protected function __construct(?int $mode, bool $isNot = false)
    {
        $this->mode = Mode::get($mode);
        $this->isNot = $isNot;
    }
    
    final public function not(): self
    {
        return new static($this->mode, true);
    }
    
    final protected function get(Filter $filter): Filter
    {
        return $this->isNot ? $filter->negate() : $filter;
    }
}