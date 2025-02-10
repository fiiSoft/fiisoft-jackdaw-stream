<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\ValRef;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\String\StringFilter;

abstract class FilterAdapterFactory
{
    private FilterPicker $filterPicker;
    
    public function __construct(FilterPicker $filterPicker)
    {
        $this->filterPicker = $filterPicker;
    }
    
    final public function getPicker(bool $negation = false): FilterPicker
    {
        return $negation ? $this->filterPicker->not() : $this->filterPicker;
    }
    
    abstract public function createFilter(Filter $filter): Filter;
    
    abstract public function createStringFilter(StringFilter $filter): StringFilter;
}