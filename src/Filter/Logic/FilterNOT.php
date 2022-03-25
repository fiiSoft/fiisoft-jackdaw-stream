<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Logic;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Predicate\Predicate;

final class FilterNOT implements Filter
{
    private Filter $filter;
    
    /**
     * @param Filter|Predicate|callable|mixed $filter
     */
    public function __construct($filter)
    {
        $this->filter = Filters::getAdapter($filter);
    }
    
    public function isAllowed($value, $key, int $mode = Check::VALUE): bool
    {
        return !$this->filter->isAllowed($value, $key, $mode);
    }
}