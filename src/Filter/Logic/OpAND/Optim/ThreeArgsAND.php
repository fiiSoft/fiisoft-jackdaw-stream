<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Logic\OpAND\Optim;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\FilterReady;
use FiiSoft\Jackdaw\Filter\Filters;

class ThreeArgsAND extends TwoArgsAND
{
    protected Filter $third;
    
    /**
     * @param FilterReady|callable|array<string|int, mixed>|scalar $first
     * @param FilterReady|callable|array<string|int, mixed>|scalar $second
     * @param FilterReady|callable|array<string|int, mixed>|scalar $third
     */
    public function __construct($first, $second, $third, ?int $mode = null)
    {
        parent::__construct($first, $second, $mode);
        
        $this->third = Filters::getAdapter($third, $mode);
    }
    
    public function isAllowed($value, $key = null): bool
    {
        return $this->first->isAllowed($value, $key)
            && $this->second->isAllowed($value, $key)
            && $this->third->isAllowed($value, $key);
    }
    
    /**
     * @inheritDoc
     */
    protected function collectFilters(): array
    {
        $filters = parent::collectFilters();
        $filters[] = $this->third;
        
        return $filters;
    }
}