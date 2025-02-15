<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Logic;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Internal\Check;

final class FilterNOT extends BaseLogicFilter
{
    private Filter $filter;
    
    protected function __construct(Filter $filter)
    {
        parent::__construct();
        
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
            if (!$this->filter->isAllowed($value, $key)) {
                yield $key => $value;
            }
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
}