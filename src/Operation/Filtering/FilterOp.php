<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Filtering;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Filtering\FilterData\FilterConditionalData;
use FiiSoft\Jackdaw\Operation\Internal\Operations;
use FiiSoft\Jackdaw\Operation\Operation;
use FiiSoft\Jackdaw\Stream;

final class FilterOp extends StackableFilter
{
    public function handle(Signal $signal): void
    {
        if ($this->filter->isAllowed($signal->item->value, $signal->item->key)) {
            $this->next->handle($signal);
        }
    }
    
    public function buildStream(iterable $stream): iterable
    {
        return $this->filter->buildStream($stream);
    }
    
    public function filterData(): FilterConditionalData
    {
        return new FilterConditionalData($this->filter, false);
    }
    
    public function createFind(Stream $stream): Operation
    {
        return Operations::find($stream, $this->filter);
    }
}