<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Condition\Adapter;

use FiiSoft\Jackdaw\Condition\Condition;
use FiiSoft\Jackdaw\Filter\Filter;

final class FilterAdapter implements Condition
{
    /** @var Filter */
    private $filter;
    
    public function __construct(Filter $filter)
    {
        $this->filter = $filter;
    }
    
    public function isTrueFor($value, $key): bool
    {
        return $this->filter->isAllowed($value, $key);
    }
}