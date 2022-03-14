<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Internal;

use FiiSoft\Jackdaw\Filter\Filter;

final class FilterData
{
    public Filter $filter;
    public bool $negation;
    public int $mode;
    
    public function __construct(Filter $filter, bool $negation, int $mode)
    {
        $this->filter = $filter;
        $this->negation = $negation;
        $this->mode = $mode;
    }
}