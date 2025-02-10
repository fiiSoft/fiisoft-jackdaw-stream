<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\ValRef\Picker;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\ValRef\FilterAdapterFactory;
use FiiSoft\Jackdaw\Filter\ValRef\FilterPicker;

abstract class BasePicker
{
    protected bool $isNot;
    
    private FilterAdapterFactory $factory;
    
    final public function __construct(FilterAdapterFactory $factory, bool $isNot = false)
    {
        $this->factory = $factory;
        $this->isNot = $isNot;
    }
    
    final protected function createFilter(Filter $filter): Filter
    {
        $adapter = $this->factory->createFilter($filter);
        
        return $this->isNot ? $adapter->negate() : $adapter;
    }
    
    final protected function picker(): FilterPicker
    {
        return $this->factory->getPicker($this->isNot);
    }
    
    /**
     * @return static
     */
    final protected function negate()
    {
        return new static($this->factory, !$this->isNot);
    }
}