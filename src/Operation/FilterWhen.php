<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Condition\Condition;
use FiiSoft\Jackdaw\Condition\Conditions;
use FiiSoft\Jackdaw\Filter\Filter as FilterStrategy;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Filter\Internal\FilterData;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Operation\Internal\FilterSingle;
use FiiSoft\Jackdaw\Predicate\Predicate;

final class FilterWhen extends BaseOperation implements FilterSingle
{
    private Condition $condition;
    private FilterStrategy $filterStrategy;
    
    private bool $negation;
    private int $mode;
    
    /**
     * @param Condition|Predicate|FilterStrategy|callable $condition
     * @param FilterStrategy|Predicate|callable|mixed $filter
     */
    public function __construct($condition, $filter, bool $negation = false, int $mode = Check::VALUE)
    {
        $this->condition = Conditions::getAdapter($condition, $mode);
        $this->filterStrategy = Filters::getAdapter($filter);
        $this->negation = $negation;
        $this->mode = Check::getMode($mode);
    }
    
    public function handle(Signal $signal): void
    {
        if (!$this->condition->isTrueFor($signal->item->value, $signal->item->key)
            || ($this->negation
                XOR $this->filterStrategy->isAllowed($signal->item->value, $signal->item->key, $this->mode))
        ) {
            $this->next->handle($signal);
        }
    }
    
    public function filterData(): FilterData
    {
        return new FilterData($this->filterStrategy, $this->negation, $this->mode, $this->condition);
    }
}