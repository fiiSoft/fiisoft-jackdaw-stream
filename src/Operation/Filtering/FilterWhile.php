<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Filtering;

use FiiSoft\Jackdaw\Condition\Condition;
use FiiSoft\Jackdaw\Condition\ConditionReady;
use FiiSoft\Jackdaw\Condition\Conditions;
use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\FilterReady;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class FilterWhile extends BaseOperation
{
    private Condition $condition;
    private Filter $filter;
    
    private bool $until;
    private bool $active = true;
    
    /**
     * @param ConditionReady|callable $condition
     * @param FilterReady|callable|mixed $filter
     */
    public function __construct($condition, $filter, ?int $mode = null, bool $until = false)
    {
        $this->condition = Conditions::getAdapter($condition, $mode);
        $this->filter = Filters::getAdapter($filter, $mode);
        $this->until = $until;
    }
    
    public function handle(Signal $signal): void
    {
        if ($this->until XOR $this->condition->isTrueFor($signal->item->value, $signal->item->key)) {
            if ($this->filter->isAllowed($signal->item->value, $signal->item->key)) {
                $this->next->handle($signal);
            }
        } else {
            $signal->forget($this);
            $this->next->handle($signal);
        }
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($this->active) {
                if ($this->until XOR $this->condition->isTrueFor($value, $key)) {
                    if (!$this->filter->isAllowed($value, $key)) {
                        continue;
                    }
                } else {
                    $this->active = false;
                }
            }
            
            yield $key => $value;
        }
    }
}