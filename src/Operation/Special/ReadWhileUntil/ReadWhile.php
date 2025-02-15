<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Special\ReadWhileUntil;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\Operations;
use FiiSoft\Jackdaw\Operation\Operation;
use FiiSoft\Jackdaw\Operation\Special\ReadWhileUntil;

final class ReadWhile extends ReadWhileUntil
{
    public function handle(Signal $signal): void
    {
        if ($this->isFirstTime) {
            $this->isFirstTime = false;
            $signal->swapHead($this);
        } elseif ($this->filter->isAllowed($signal->item->value, $signal->item->key)) {
            if ($this->reindex) {
                $signal->item->key = ++$this->index;
            }
            
            $this->next->handle($signal);
        } else {
            $this->index = -1;
            $this->isFirstTime = true;
            
            $this->consumer->consume($signal->item->value, $signal->item->key);
            
            $signal->restoreHead();
            $signal->setNextItem($signal->item);
        }
    }
    
    public function createFilterOperation(): Operation
    {
        return Operations::filter($this->filter);
    }
}