<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Condition\Adapter;

use FiiSoft\Jackdaw\Condition\Condition;
use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Internal\Check;

final class FilterAdapter implements Condition
{
    private Filter $filter;
    private int $mode;
    
    public function __construct(Filter $filter, int $mode = Check::VALUE)
    {
        $this->filter = $filter;
        $this->mode = Check::getMode($mode);
    }
    
    public function isTrueFor($value, $key): bool
    {
        return $this->filter->isAllowed($value, $key, $this->mode);
    }
}