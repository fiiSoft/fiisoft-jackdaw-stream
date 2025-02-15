<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Filtering;

use FiiSoft\Jackdaw\Condition\Condition;
use FiiSoft\Jackdaw\Condition\ConditionReady;
use FiiSoft\Jackdaw\Condition\Conditions;
use FiiSoft\Jackdaw\Filter\FilterReady;
use FiiSoft\Jackdaw\Filter\Internal\FilterData;
use FiiSoft\Jackdaw\Internal\Signal;

final class FilterWhen extends StackableFilter
{
    private Condition $condition;
    
    /**
     * @param ConditionReady|callable $condition
     * @param FilterReady|callable|mixed $filter
     */
    public function __construct($condition, $filter, ?int $mode = null)
    {
        parent::__construct($filter, $mode);
        
        $this->condition = Conditions::getAdapter($condition, $mode);
    }
    
    public function handle(Signal $signal): void
    {
        if (!$this->condition->isTrueFor($signal->item->value, $signal->item->key)
            || $this->filter->isAllowed($signal->item->value, $signal->item->key)
        ) {
            $this->next->handle($signal);
        }
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if (!$this->condition->isTrueFor($value, $key) || $this->filter->isAllowed($value, $key)) {
                yield $key => $value;
            }
        }
    }
    
    public function filterData(): FilterData
    {
        return new FilterData($this->filter, false, $this->condition);
    }
}