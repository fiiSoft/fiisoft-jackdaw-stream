<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\ValRef\Adapter\Reference;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\String\StringFilter;
use FiiSoft\Jackdaw\Filter\ValRef\FilterAdapterFactory;
use FiiSoft\Jackdaw\Filter\ValRef\FilterPicker;

final class ReferenceFilterFactory extends FilterAdapterFactory
{
    /** @var mixed REFERENCE */
    private $variable;
    
    /**
     * @param mixed $variable REFERENCE
     */
    public function __construct(&$variable, FilterPicker $filterPicker)
    {
        parent::__construct($filterPicker);
        
        $this->variable = &$variable;
    }
    
    public function createFilter(Filter $filter): Filter
    {
        return new ReferenceFilter($this->variable, $filter);
    }
    
    public function createStringFilter(StringFilter $filter): StringFilter
    {
        return new ReferenceStringFilter($this->variable, $filter);
    }
}