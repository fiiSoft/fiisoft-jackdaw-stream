<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Filtering;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\FilterReady;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Operation\Filtering\FilterData\FilterConditionalData;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

abstract class StackableFilter extends BaseOperation
{
    protected Filter $filter;
    
    /**
     * @param FilterReady|callable|mixed $filter
     */
    public function __construct($filter, ?int $mode = null)
    {
        $this->filter = Filters::getAdapter($filter, $mode);
    }
    
    abstract public function filterData(): FilterConditionalData;
}