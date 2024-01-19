<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Logic\OpAND;

use FiiSoft\Jackdaw\Filter\Filter;

final class FilterAND extends BaseAND
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        foreach ($this->filters as $filter) {
            if ($filter->isAllowed($value, $key)) {
                continue;
            }
            
            return false;
        }
        
        return true;
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($this->filters as $filter) {
            $stream = $filter->buildStream($stream);
        }
     
        return $stream;
    }
    
    public function add(Filter $filter): void
    {
        $this->filters[] = $filter;
        $this->mode = null;
    }
}