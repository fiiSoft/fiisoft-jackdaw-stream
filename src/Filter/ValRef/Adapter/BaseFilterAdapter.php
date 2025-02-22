<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\ValRef\Adapter;

use FiiSoft\Jackdaw\Filter\BaseFilter;
use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Internal\Check;

abstract class BaseFilterAdapter extends BaseFilter
{
    protected Filter $filter;
    
    public function __construct(Filter $filter)
    {
        parent::__construct(Check::VALUE);
        
        $this->filter = $filter->checkValue();
    }
    
    final public function inMode(?int $mode): Filter
    {
        return $this;
    }
    
    public function equals(Filter $other): bool
    {
        return $other instanceof $this
            && $other->filter->equals($this->filter)
            && parent::equals($other);
    }
}