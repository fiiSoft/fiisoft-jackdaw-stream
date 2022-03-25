<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Logic;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Internal\Check;

final class FilterAND extends LogicFilter
{
    public function isAllowed($value, $key, int $mode = Check::VALUE): bool
    {
        if ($mode === Check::ANY) {
            foreach ([Check::VALUE, Check::KEY] as $check) {
                $isSatisfied = true;
                foreach ($this->filters as $filter) {
                    if ($filter->isAllowed($value, $key, $check)) {
                        continue;
                    }
                    $isSatisfied = false;
                    break;
                }
    
                if ($isSatisfied) {
                    return true;
                }
            }
            
            return false;
        }
        
        foreach ($this->filters as $filter) {
            if ($filter->isAllowed($value, $key, $mode)) {
                continue;
            }
            
            return false;
        }
        
        return true;
    }
    
    public function add(Filter $filter): void
    {
        $this->filters[] = $filter;
    }
}