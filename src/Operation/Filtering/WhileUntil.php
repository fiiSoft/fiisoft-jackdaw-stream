<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Filtering;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\FilterReady;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

abstract class WhileUntil extends BaseOperation
{
    protected Filter $condition;
    protected Filter $filter;
    
    protected bool $active = true;
    
    /**
     * @param FilterReady|callable|array<string|int, mixed>|scalar $condition
     * @param FilterReady|callable|array<string|int, mixed>|scalar $filter
     */
    public function __construct($condition, $filter, ?int $mode = null)
    {
        $this->condition = Filters::getAdapter($condition, $mode);
        $this->filter = Filters::getAdapter($filter, $mode);
    }
}