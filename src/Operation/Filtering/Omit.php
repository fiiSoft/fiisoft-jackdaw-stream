<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Filtering;

use FiiSoft\Jackdaw\Filter\Logic\FilterNOT;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Filtering\FilterData\FilterConditionalData;
use FiiSoft\Jackdaw\Operation\Internal\Operations;
use FiiSoft\Jackdaw\Operation\Operation;
use FiiSoft\Jackdaw\Stream;

final class Omit extends StackableFilter
{
    public function handle(Signal $signal): void
    {
        if ($this->filter->isAllowed($signal->item->value, $signal->item->key)) {
            return;
        }
        
        $this->next->handle($signal);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        if ($this->filter instanceof FilterNOT) {
            return $this->filter->wrappedFilter()->buildStream($stream);
        }
        
        return (function () use ($stream): iterable {
            foreach ($stream as $key => $value) {
                if ($this->filter->isAllowed($value, $key)) {
                    continue;
                }
                
                yield $key => $value;
            }
        })();
    }
    
    public function filterData(): FilterConditionalData
    {
        return new FilterConditionalData($this->filter, true);
    }
    
    public function createFind(Stream $stream): Operation
    {
        return Operations::find($stream, $this->filter->negate());
    }
}