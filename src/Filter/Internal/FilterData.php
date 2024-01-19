<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Internal;

use FiiSoft\Jackdaw\Condition\Condition;
use FiiSoft\Jackdaw\Filter\Filter;

final class FilterData
{
    public ?Condition $condition;
    
    public Filter $filter;
    
    public bool $negation;
    
    public function __construct(Filter $filter, bool $negation, ?Condition $condition = null)
    {
        $this->filter = $filter;
        $this->negation = $negation;
        $this->condition = $condition;
    }
}