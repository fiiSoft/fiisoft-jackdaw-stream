<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Size;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\SingleFilterHolder;

abstract class SizeFilter extends SingleFilterHolder
{
    abstract protected static function create(int $mode, Filter $filter): self;
    
    final protected function __construct(Filter $filter, int $mode)
    {
        parent::__construct($filter, $mode);
    }
    
    final public function negate(): Filter
    {
        return $this->createFilter($this->filter->negate());
    }
    
    final public function inMode(?int $mode): Filter
    {
        return $mode !== null && $mode !== $this->mode
            ? static::create($mode, $this->filter)
            : $this;
    }
    
    final protected function createFilter(Filter $filter): Filter
    {
        return static::create($this->mode, $filter);
    }
}