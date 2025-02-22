<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Sending\SendWhileUntil;

use FiiSoft\Jackdaw\Internal\Signal;

final class SendUntil extends SendWhileUntil
{
    public function handle(Signal $signal): void
    {
        if ($this->condition->isAllowed($signal->item->value, $signal->item->key)) {
            $signal->forget($this);
        } else {
            $this->consumer->consume($signal->item->value, $signal->item->key);
        }
        
        $this->next->handle($signal);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($this->isActive) {
                if ($this->condition->isAllowed($value, $key)) {
                    $this->isActive = false;
                } else {
                    $this->consumer->consume($value, $key);
                }
            }
            
            yield $key => $value;
        }
    }
}