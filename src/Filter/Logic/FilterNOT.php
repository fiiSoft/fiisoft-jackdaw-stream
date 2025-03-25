<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Logic;

use FiiSoft\Jackdaw\Filter\AbstractFilter;
use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\FilterAdjuster;
use FiiSoft\Jackdaw\Internal\Check;

final class FilterNOT extends AbstractFilter
{
    private Filter $filter;
    
    protected function __construct(Filter $filter)
    {
        $this->filter = $filter;
    }
    
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return !$this->filter->isAllowed($value, $key);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($this->filter->isAllowed($value, $key)) {
                continue;
            }
            
            yield $key => $value;
        }
    }
    
    public function inMode(?int $mode): Filter
    {
        if ($mode !== null && ($mode !== $this->filter->getMode() || $mode !== $this->negatedMode())) {
            if ($mode === Check::BOTH) {
                $mode = Check::ANY;
            } elseif ($mode === Check::ANY) {
                $mode = Check::BOTH;
            }
            
            return new self($this->filter->inMode($mode));
        }
        
        return $this;
    }
    
    public function getMode(): ?int
    {
        return $this->filter->getMode();
    }
    
    public function negate(): Filter
    {
        $negation = $this->filter->negate();
        
        return $negation instanceof self ? $negation->wrappedFilter() : $negation;
    }
    
    public function wrappedFilter(): Filter
    {
        return $this->filter;
    }
    
    public function equals(Filter $other): bool
    {
        return $other === $this || $other instanceof $this && $other->filter->equals($this->filter);
    }
    
    public function adjust(FilterAdjuster $adjuster): Filter
    {
        $adjusted = $this->filter->adjust($adjuster);
        
        if ($adjusted->equals($this->filter)) {
            return $this;
        }
        
        return new self($adjusted);
    }
}