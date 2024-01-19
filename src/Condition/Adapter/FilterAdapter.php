<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Condition\Adapter;

use FiiSoft\Jackdaw\Condition\Condition;
use FiiSoft\Jackdaw\Filter\Filter;

final class FilterAdapter implements Condition
{
    private Filter $filter;
    
    public function __construct(Filter $filter, ?int $mode = null)
    {
        $this->filter = $filter->inMode($mode);
    }
    
    public function isTrueFor($value, $key): bool
    {
        return $this->filter->isAllowed($value, $key);
    }
}