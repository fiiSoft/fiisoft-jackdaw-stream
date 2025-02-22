<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Filtering;

use FiiSoft\Jackdaw\Internal\Signal;

final class FilterWhile extends WhileUntil
{
    public function handle(Signal $signal): void
    {
        if ($this->condition->isAllowed($signal->item->value, $signal->item->key)) {
            if ($this->filter->isAllowed($signal->item->value, $signal->item->key)) {
                $this->next->handle($signal);
            }
        } else {
            $signal->forget($this);
            $this->next->handle($signal);
        }
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($this->active) {
                if ($this->condition->isAllowed($value, $key)) {
                    if ($this->filter->isAllowed($value, $key)) {
                        yield $key => $value;
                    }
                    
                    continue;
                }
                
                $this->active = false;
            }
            
            yield $key => $value;
        }
    }
}