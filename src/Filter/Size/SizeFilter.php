<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Size;

use FiiSoft\Jackdaw\Filter\BaseFilter;
use FiiSoft\Jackdaw\Filter\Filter;

abstract class SizeFilter extends BaseFilter
{
    protected Filter $filter;
    
    abstract protected static function create(int $mode, Filter $filter): self;
    
    final protected function __construct(int $mode, Filter $filter)
    {
        parent::__construct($mode);
        
        $this->filter = $filter;
    }
    
    final public function negate(): Filter
    {
        return static::create($this->mode, $this->filter->negate());
    }
    
    final public function inMode(?int $mode): Filter
    {
        return $mode !== null && $mode !== $this->mode
            ? static::create($mode, $this->filter)
            : $this;
    }
    
    final public function equals(Filter $other): bool
    {
        return $other instanceof $this
            && $other->filter->equals($this->filter)
            && parent::equals($other);
    }
}