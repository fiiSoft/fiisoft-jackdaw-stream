<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Filtering;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\Operations;
use FiiSoft\Jackdaw\Operation\Internal\PossiblyInversible;
use FiiSoft\Jackdaw\Operation\Operation;

final class SkipUntil extends PossiblyInversible
{
    private bool $isActive = true;
    
    public function handle(Signal $signal): void
    {
        if ($this->filter->isAllowed($signal->item->value, $signal->item->key)) {
            $this->next->handle($signal);
            $signal->forget($this);
        }
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($this->isActive) {
                if ($this->filter->isAllowed($value, $key)) {
                    $this->isActive = false;
                } else {
                    continue;
                }
            }
            
            yield $key => $value;
        }
    }
    
    protected function inversedOperation(Filter $filter): Operation
    {
        return Operations::skipWhile($filter);
    }
}