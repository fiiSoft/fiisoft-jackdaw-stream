<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Logic;

use FiiSoft\Jackdaw\Filter\AbstractFilter;
use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\FilterAdjuster;

abstract class BaseLogicFilter extends AbstractFilter implements CompoundFilter
{
    /** @var Filter[] */
    private array $myFilters = [];
    
    final public function adjust(FilterAdjuster $adjuster): Filter
    {
        $isDifferent = false;
        $adjustedFilters = [];
        
        foreach ($this->getFilters() as $filter) {
            $adjusted = $filter->adjust($adjuster);
            $adjustedFilters[] = $adjusted;
            
            if (!$isDifferent && !$adjusted->equals($filter)) {
                $isDifferent = true;
            }
        }
        
        if ($isDifferent) {
            return $this->createFilter($adjustedFilters);
        }
        
        return $this;
    }
    
    final public function getFilters(): array
    {
        if (empty($this->myFilters)) {
            $this->myFilters = $this->collectFilters();
        }
        
        return $this->myFilters;
    }
    
    /**
     * @return Filter[]
     */
    abstract protected function collectFilters(): array;
    
    /**
     * @param Filter[] $filters
     */
    abstract protected function createFilter(array $filters, ?int $mode = null): Filter;
}