<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Special\WhileUntil;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\Operations;
use FiiSoft\Jackdaw\Operation\Internal\PossiblyInversible;
use FiiSoft\Jackdaw\Operation\Operation;

final class WhileTrue extends PossiblyInversible
{
    public function handle(Signal $signal): void
    {
        if ($this->filter->isAllowed($signal->item->value, $signal->item->key)) {
            $this->next->handle($signal);
        } else {
            $signal->limitReached($this);
        }
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($this->filter->isAllowed($value, $key)) {
                yield $key => $value;
            } else {
                break;
            }
        }
    }
    
    protected function inversedOperation(Filter $filter): Operation
    {
        return Operations::until($filter);
    }
}