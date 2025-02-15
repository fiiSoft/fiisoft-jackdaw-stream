<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Special\WhileUntil;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\Operations;
use FiiSoft\Jackdaw\Operation\Internal\PossiblyInversible;
use FiiSoft\Jackdaw\Operation\Operation;

final class UntilTrue extends PossiblyInversible
{
    public function handle(Signal $signal): void
    {
        if ($this->filter->isAllowed($signal->item->value, $signal->item->key)) {
            $signal->limitReached($this);
        } else {
            $this->next->handle($signal);
        }
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($this->filter->isAllowed($value, $key)) {
                break;
            }
            
            yield $key => $value;
        }
    }
    
    protected function inversedOperation(Filter $filter): Operation
    {
        return Operations::while($filter);
    }
}