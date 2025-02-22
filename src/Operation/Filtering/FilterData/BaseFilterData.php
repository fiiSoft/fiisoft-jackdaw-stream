<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Filtering\FilterData;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\IdleFilter;
use FiiSoft\Jackdaw\Filter\Logic\FilterNOT;

abstract class BaseFilterData
{
    public Filter $filter;
    
    public bool $negation;
    
    public function __construct(Filter $filter, bool $negation)
    {
        $this->filter = $filter;
        $this->negation = $negation;
    }
    
    public function mergeWith(BaseFilterData $other): bool
    {
        if ($this->filter->equals($other->filter)) {
            if ($this->negation !== $other->negation) {
                $this->filter = IdleFilter::false($this->filter->getMode());
                $this->negation = false;
            }
            
            return true;
        }
        
        if ($this->negation) {
            if ($other->negation) {
                //~a AND ~b = a OR b
                $this->filter = $this->filter->or($other->filter);
                
                return true;
            }

            //~a AND b
            if ($this->filter instanceof FilterNOT) {
                $this->filter = $this->filter->wrappedFilter()->and($other->filter);
            } else {
                $this->filter = $this->filter->negate()->and($other->filter);
            }
        } elseif ($other->negation) {
            //a AND ~b
            if ($other->filter instanceof FilterNOT) {
                $this->filter = $this->filter->and($other->filter->wrappedFilter());
            } else {
                $this->filter = $this->filter->and($other->filter->negate());
            }
        } else {
            //a AND b
            $this->filter = $this->filter->and($other->filter);
        }
        
        $this->negation = false;
        
        return true;
    }
}