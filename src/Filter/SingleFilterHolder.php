<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter;

abstract class SingleFilterHolder extends BaseFilter
{
    protected Filter $filter;
    
    protected function __construct(Filter $filter, ?int $mode = null)
    {
        parent::__construct($mode);
        
        $this->filter = $filter;
    }
    
    final public function adjust(FilterAdjuster $adjuster): Filter
    {
        $meAdjusted = parent::adjust($adjuster);
        
        if ($meAdjusted->equals($this)) {
            $filterAdjusted = $this->filter->adjust($adjuster);
            
            if ($filterAdjusted->equals($this->filter)) {
                return $this;
            }
            
            return $this->createFilter($filterAdjusted);
        }
        
        return $meAdjusted;
    }
    
    public function equals(Filter $other): bool
    {
        return $other === $this || $other instanceof $this
            && $other->filter->equals($this->filter)
            && parent::equals($other);
    }
    
    abstract protected function createFilter(Filter $filter): Filter;
}