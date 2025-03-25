<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Logic\OpOR\Optim;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\FilterReady;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Filter\Logic\BaseMultiLogicFilter;
use FiiSoft\Jackdaw\Filter\Logic\OpAND\BaseAND;
use FiiSoft\Jackdaw\Filter\Logic\OpOR\BaseOR;
use FiiSoft\Jackdaw\Filter\Logic\OpOR\LogicOR;

class TwoArgsOR extends BaseMultiLogicFilter implements LogicOR
{
    protected Filter $first, $second;
    
    /**
     * @param FilterReady|callable|array<string|int, mixed>|scalar $first
     * @param FilterReady|callable|array<string|int, mixed>|scalar $second
     */
    public function __construct($first, $second, ?int $mode = null)
    {
        $this->first = Filters::getAdapter($first, $mode);
        $this->second = Filters::getAdapter($second, $mode);
    }
    
    public function isAllowed($value, $key = null): bool
    {
        return $this->first->isAllowed($value, $key) || $this->second->isAllowed($value, $key);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($this->first->isAllowed($value, $key) || $this->second->isAllowed($value, $key)) {
                yield $key => $value;
            }
        }
    }
    
    final public function inMode(?int $mode): Filter
    {
        return $mode !== null && $mode !== $this->getMode()
            ? $this->createFilter($this->getFilters(), $mode)
            : $this;
    }
    
    /**
     * @inheritDoc
     */
    final protected function createFilter(array $filters, ?int $mode = null): Filter
    {
        return BaseOR::create($filters, $mode);
    }
    
    final public function negate(): Filter
    {
        return BaseAND::create($this->negatedFilters(), $this->negatedMode());
    }
    
    /**
     * @inheritDoc
     */
    protected function collectFilters(): array
    {
        return [$this->first, $this->second];
    }
}