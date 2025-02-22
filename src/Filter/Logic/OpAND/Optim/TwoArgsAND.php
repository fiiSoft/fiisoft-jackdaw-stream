<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Logic\OpAND\Optim;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\FilterReady;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Filter\Logic\BaseCompoundFilter;
use FiiSoft\Jackdaw\Filter\Logic\OpAND\BaseAND;
use FiiSoft\Jackdaw\Filter\Logic\OpAND\LogicAND;
use FiiSoft\Jackdaw\Filter\Logic\OpOR\BaseOR;

class TwoArgsAND extends BaseCompoundFilter implements LogicAND
{
    protected Filter $first, $second;
    
    /**
     * @param FilterReady|callable|mixed $first
     * @param FilterReady|callable|mixed $second
     */
    public function __construct($first, $second, ?int $mode = null)
    {
        parent::__construct();
        
        $this->first = Filters::getAdapter($first, $mode);
        $this->second = Filters::getAdapter($second, $mode);
    }
    
    public function isAllowed($value, $key = null): bool
    {
        return $this->first->isAllowed($value, $key) && $this->second->isAllowed($value, $key);
    }
    
    final public function buildStream(iterable $stream): iterable
    {
        foreach ($this->getFilters() as $filter) {
            $stream = $filter->buildStream($stream);
        }
        
        return $stream;
    }
    
    final public function inMode(?int $mode): Filter
    {
        return $mode !== null && $mode !== $this->getMode()
            ? BaseAND::create($this->getFilters(), $mode)
            : $this;
    }
    
    final public function negate(): Filter
    {
        return BaseOR::create($this->negatedFilters(), $this->negatedMode());
    }
    
    /**
     * @return Filter[]
     */
    public function getFilters(): array
    {
        return [$this->first, $this->second];
    }
}