<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Filtering;

use FiiSoft\Jackdaw\Filter\Internal\FilterData;
use FiiSoft\Jackdaw\Filter\Logic\FilterNOT;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Terminating\Find;
use FiiSoft\Jackdaw\Stream;

final class Filter extends FilterSingle
{
    public function handle(Signal $signal): void
    {
        if ($this->negation XOR $this->filter->isAllowed($signal->item->value, $signal->item->key)) {
            $this->next->handle($signal);
        }
    }
    
    public function buildStream(iterable $stream): iterable
    {
        if ($this->negation) {
            if ($this->filter instanceof FilterNOT) {
                return $this->filter->wrappedFilter()->buildStream($stream);
            }

            return (function (iterable $stream): iterable {
                foreach ($stream as $key => $value) {
                    if ($this->filter->isAllowed($value, $key)) {
                        continue;
                    }
                    
                    yield $key => $value;
                }
            })($stream);
        }
        
        return $this->filter->buildStream($stream);
    }
    
    public function filterData(): FilterData
    {
        return new FilterData($this->filter, $this->negation);
    }
    
    public function createFind(Stream $stream): Find
    {
        return new Find(
            $stream,
            $this->negation ? $this->filter->negate() : $this->filter,
            $this->filter->getMode()
        );
    }
}