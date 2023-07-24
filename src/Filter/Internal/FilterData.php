<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Internal;

use FiiSoft\Jackdaw\Condition\Condition;
use FiiSoft\Jackdaw\Filter\Filter;

final class FilterData
{
    public ?Condition $condition;
    public Filter $filter;
    public bool $negation;
    public int $mode;
    
    public function __construct(Filter $filter, bool $negation, int $mode, ?Condition $condition = null)
    {
        $this->filter = $filter;
        $this->negation = $negation;
        $this->mode = $mode;
        
        $this->condition = $condition;
    }
}