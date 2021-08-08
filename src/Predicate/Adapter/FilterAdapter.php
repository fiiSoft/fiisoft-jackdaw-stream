<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Predicate\Adapter;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Predicate\Predicate;

final class FilterAdapter implements Predicate
{
    private Filter $filter;
    
    public function __construct(Filter $filter)
    {
        $this->filter = $filter;
    }
    
    public function isSatisfiedBy($value, $key = null, int $mode = Check::VALUE): bool
    {
        return $this->filter->isAllowed($value, $key, $mode);
    }
}