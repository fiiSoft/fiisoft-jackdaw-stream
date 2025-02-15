<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Filtering;

use FiiSoft\Jackdaw\Condition\Condition;
use FiiSoft\Jackdaw\Condition\ConditionReady;
use FiiSoft\Jackdaw\Condition\Conditions;
use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\FilterReady;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

abstract class WhileUntil extends BaseOperation
{
    protected Condition $condition;
    protected Filter $filter;
    
    protected bool $active = true;
    
    /**
     * @param ConditionReady|callable $condition
     * @param FilterReady|callable|mixed $filter
     */
    public function __construct($condition, $filter, ?int $mode = null)
    {
        $this->condition = Conditions::getAdapter($condition, $mode);
        $this->filter = Filters::getAdapter($filter, $mode);
    }
}