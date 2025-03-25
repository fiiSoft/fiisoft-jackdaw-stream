<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Filtering;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\FilterReady;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Filtering\FilterData\FilterConditionalData;

final class OmitWhen extends StackableFilter
{
    private Filter $condition;
    
    /**
     * @param FilterReady|callable|array<string|int, mixed>|scalar $condition
     * @param FilterReady|callable|array<string|int, mixed>|scalar $filter
     */
    public function __construct($condition, $filter, ?int $mode = null)
    {
        parent::__construct($filter, $mode);
        
        $this->condition = Filters::getAdapter($condition, $mode);
    }
    
    public function handle(Signal $signal): void
    {
        if ($this->condition->isAllowed($signal->item->value, $signal->item->key)
            && $this->filter->isAllowed($signal->item->value, $signal->item->key)
        ) {
            return;
        }
        
        $this->next->handle($signal);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($this->condition->isAllowed($value, $key) && $this->filter->isAllowed($value, $key)) {
                continue;
            }
            
            yield $key => $value;
        }
    }
    
    public function filterData(): FilterConditionalData
    {
        return new FilterConditionalData($this->filter, true, $this->condition);
    }
}