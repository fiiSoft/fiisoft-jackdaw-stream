<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\String;

use FiiSoft\Jackdaw\Filter\Adjuster\StringFilterAdjuster;
use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\FilterAdjuster;
use FiiSoft\Jackdaw\Filter\FilterWrapper;
use FiiSoft\Jackdaw\Internal\Check;

final class StringFilterPhony implements StringFilter, FilterWrapper
{
    private Filter $filter;
    
    private bool $ignoreCase;
    
    public function __construct(Filter $filter, bool $ignoreCase)
    {
        $this->filter = $filter;
        $this->ignoreCase = $ignoreCase;
    }
    
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return $this->filter->isAllowed($value, $key);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        return $this->filter->buildStream($stream);
    }
    
    public function inMode(?int $mode): StringFilter
    {
        return $mode !== null && $mode !== $this->filter->getMode()
            ? $this->createFilter($this->filter->inMode($mode))
            : $this;
    }
    
    public function getMode(): ?int
    {
        return $this->filter->getMode();
    }
    
    public function checkValue(): StringFilter
    {
        return $this->inMode(Check::VALUE);
    }
    
    public function checkKey(): StringFilter
    {
        return $this->inMode(Check::KEY);
    }
    
    public function checkBoth(): StringFilter
    {
        return $this->inMode(Check::BOTH);
    }
    
    public function checkAny(): StringFilter
    {
        return $this->inMode(Check::ANY);
    }
    
    public function negate(): StringFilter
    {
        return $this->createFilter($this->filter->negate());
    }
    
    public function ignoreCase(): StringFilter
    {
        return $this->createAdjusted(true);
    }
    
    public function caseSensitive(): StringFilter
    {
        return $this->createAdjusted(false);
    }
    
    private function createAdjusted(bool $ignoreCase): self
    {
        $adjusted = $this->filter->adjust(new StringFilterAdjuster($ignoreCase));
        
        if ($this->ignoreCase === $ignoreCase && $adjusted->equals($this->filter)) {
            return $this;
        }
        
        return $this->createFilter($adjusted, $ignoreCase);
    }
    
    public function isCaseInsensitive(): bool
    {
        return $this->ignoreCase;
    }
    
    /**
     * @inheritDoc
     */
    public function and($filter): StringFilter
    {
        return $this->createFilter($this->filter->and($filter));
    }
    
    /**
     * @inheritDoc
     */
    public function andNot($filter): StringFilter
    {
        return $this->createFilter($this->filter->andNot($filter));
    }
    
    /**
     * @inheritDoc
     */
    public function or($filter): StringFilter
    {
        return $this->createFilter($this->filter->or($filter));
    }
    
    /**
     * @inheritDoc
     */
    public function orNot($filter): StringFilter
    {
        return $this->createFilter($this->filter->orNot($filter));
    }
    
    /**
     * @inheritDoc
     */
    public function xor($filter): StringFilter
    {
        return $this->createFilter($this->filter->xor($filter));
    }
    
    /**
     * @inheritDoc
     */
    public function xnor($filter): StringFilter
    {
        return $this->createFilter($this->filter->xnor($filter));
    }
    
    public function adjust(FilterAdjuster $adjuster): Filter
    {
        $meAdjusted = $adjuster->adjust($this);
        
        if ($meAdjusted->equals($this)) {
            $filterAdjusted = $this->filter->adjust($adjuster);
            
            if ($filterAdjusted->equals($this->filter)) {
                return $this;
            }
            
            return $this->createFilter($filterAdjusted, $this->ignoreCase);
        }
        
        return $meAdjusted;
    }
    
    private function createFilter(Filter $filter, ?bool $ignoreCase = null): self
    {
        return new self($filter, $ignoreCase ?? $this->ignoreCase);
    }
    
    public function equals(Filter $other): bool
    {
        return $other === $this || $other instanceof $this
            && $other->ignoreCase === $this->ignoreCase
            && $other->equals($this->filter);
    }
    
    public function wrappedFilter(): Filter
    {
        return $this->filter;
    }
}