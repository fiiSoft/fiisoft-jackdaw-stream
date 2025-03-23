<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\ValRef\Adapter;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\SingleFilterHolder;
use FiiSoft\Jackdaw\Internal\Check;

abstract class BaseFilterAdapter extends SingleFilterHolder
{
    protected function __construct(Filter $filter)
    {
        parent::__construct($filter->checkValue(), Check::VALUE);
    }
    
    final public function inMode(?int $mode): Filter
    {
        return $this;
    }
}