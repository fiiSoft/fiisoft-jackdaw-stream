<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Filtering;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Filtering\FilterData\FilterFieldData;

final class OmitBy extends StackableFilterBy
{
    public function handle(Signal $signal): void
    {
        if ($this->filter->isAllowed($signal->item->value[$this->field], $signal->item->key)) {
            return;
        }
        
        $this->next->handle($signal);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($this->filter->isAllowed($value[$this->field], $key)) {
                continue;
            }
            
            yield $key => $value;
        }
    }
    
    public function filterByData(): FilterFieldData
    {
        return new FilterFieldData($this->field, $this->filter, true);
    }
}