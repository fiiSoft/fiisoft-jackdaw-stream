<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Adjuster;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\FilterAdjuster;
use FiiSoft\Jackdaw\Filter\String\StringFilter;

final class StringFilterAdjuster implements FilterAdjuster
{
    private bool $ignoreCase;
    
    public function __construct(bool $ignoreCase)
    {
        $this->ignoreCase = $ignoreCase;
    }
    
    public function adjust(Filter $filter): Filter
    {
        if ($filter instanceof StringFilter) {
            return $this->adjustStringFilter($filter);
        }
        
        return $filter;
    }
    
    private function adjustStringFilter(StringFilter $filter): Filter
    {
        if ($this->ignoreCase) {
            if (!$filter->isCaseInsensitive()) {
                return $filter->ignoreCase();
            }
        } elseif ($filter->isCaseInsensitive()) {
            return $filter->caseSensitive();
        }
        
        return $filter;
    }
}