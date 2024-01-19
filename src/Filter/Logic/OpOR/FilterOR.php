<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Logic\OpOR;

use FiiSoft\Jackdaw\Filter\Filter;

final class FilterOR extends BaseOR
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        foreach ($this->filters as $filter) {
            if ($filter->isAllowed($value, $key)) {
                return true;
            }
        }
    
        return false;
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            foreach ($this->filters as $filter) {
                if ($filter->isAllowed($value, $key)) {
                    yield $key => $value;
                    continue 2;
                }
            }
        }
    }
    
    public function add(Filter $filter): void
    {
        $this->filters[] = $filter;
        $this->mode = null;
    }
}