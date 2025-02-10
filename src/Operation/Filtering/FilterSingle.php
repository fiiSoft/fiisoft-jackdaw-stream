<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Filtering;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\FilterReady;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Filter\Internal\FilterData;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

abstract class FilterSingle extends BaseOperation
{
    protected Filter $filter;
    
    protected bool $negation;
    
    /**
     * @param FilterReady|callable|mixed $filter
     */
    public function __construct($filter, bool $negation = false, ?int $mode = null)
    {
        $this->filter = Filters::getAdapter($filter, $mode);
        $this->negation = $negation;
    }
    
    abstract public function filterData(): FilterData;
}