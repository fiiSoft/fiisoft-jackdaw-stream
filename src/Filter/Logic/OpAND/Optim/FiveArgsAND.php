<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Logic\OpAND\Optim;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\FilterReady;
use FiiSoft\Jackdaw\Filter\Filters;

class FiveArgsAND extends FourArgsAND
{
    protected Filter $fifth;
    
    /**
     * @param FilterReady|callable|array<string|int, mixed>|scalar $first
     * @param FilterReady|callable|array<string|int, mixed>|scalar $second
     * @param FilterReady|callable|array<string|int, mixed>|scalar $third
     * @param FilterReady|callable|array<string|int, mixed>|scalar $fourth
     * @param FilterReady|callable|array<string|int, mixed>|scalar $fifth
     */
    public function __construct($first, $second, $third, $fourth, $fifth, ?int $mode = null)
    {
        parent::__construct($first, $second, $third, $fourth, $mode);
        
        $this->fifth = Filters::getAdapter($fifth, $mode);
    }
    
    public function isAllowed($value, $key = null): bool
    {
        return $this->first->isAllowed($value, $key)
            && $this->second->isAllowed($value, $key)
            && $this->third->isAllowed($value, $key)
            && $this->fourth->isAllowed($value, $key)
            && $this->fifth->isAllowed($value, $key);
    }
    
    /**
     * @inheritDoc
     */
    protected function collectFilters(): array
    {
        $filters = parent::collectFilters();
        $filters[] = $this->fifth;
        
        return $filters;
    }
}