<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Logic;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Internal\Check;

final class FilterOR extends LogicFilter
{
    public function isAllowed($value, $key, int $mode = Check::VALUE): bool
    {
        if ($mode === Check::BOTH) {
            foreach ([Check::VALUE, Check::KEY] as $check) {
                $isSatisfied = false;
                
                foreach ($this->filters as $filter) {
                    if ($filter->isAllowed($value, $key, $check)) {
                        $isSatisfied = true;
                        break;
                    }
                }
    
                if (!$isSatisfied) {
                    return false;
                }
            }
            
            return true;
        }
        
        foreach ($this->filters as $filter) {
            if ($filter->isAllowed($value, $key, $mode)) {
                return true;
            }
        }
    
        return false;
    }
    
    public function add(Filter $filter): void
    {
        $this->filters[] = $filter;
    }
}