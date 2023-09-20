<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Condition\Condition;
use FiiSoft\Jackdaw\Condition\ConditionReady;
use FiiSoft\Jackdaw\Condition\Conditions;
use FiiSoft\Jackdaw\Filter\Filter as FilterStrategy;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class FilterWhile extends BaseOperation
{
    private Condition $condition;
    private FilterStrategy $filterStrategy;
    
    private bool $until;
    private int $mode;
    
    /**
     * @param ConditionReady|callable $condition
     * @param FilterStrategy|callable|mixed $filter
     */
    public function __construct($condition, $filter, int $mode = Check::VALUE, bool $until = false)
    {
        $this->condition = Conditions::getAdapter($condition, $mode);
        $this->filterStrategy = Filters::getAdapter($filter);
        $this->mode = Check::getMode($mode);
        $this->until = $until;
    }
    
    public function handle(Signal $signal): void
    {
        if ($this->until XOR $this->condition->isTrueFor($signal->item->value, $signal->item->key)) {
            if ($this->filterStrategy->isAllowed($signal->item->value, $signal->item->key, $this->mode)) {
                $this->next->handle($signal);
            }
        } else {
            $signal->forget($this);
            $this->next->handle($signal);
        }
    }
}