<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Filtering\FilterData;

use FiiSoft\Jackdaw\Filter\Filter;

final class FilterConditionalData extends BaseFilterData
{
    public ?Filter $condition;
    
    public function __construct(Filter $filter, bool $negation, ?Filter $condition = null)
    {
        parent::__construct($filter, $negation);
        
        $this->condition = $condition;
    }
    
    public function mergeWith(BaseFilterData $other): bool
    {
        if ($other instanceof self) {
            if ($other->condition === null) {
                return $this->condition === null && parent::mergeWith($other);
            }
            
            return $this->condition !== null
                && $this->condition->equals($other->condition)
                && parent::mergeWith($other);
        }
        
        return false;
    }
}