<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Sending\SendWhileUntil;

use FiiSoft\Jackdaw\Internal\Signal;

final class SendWhile extends SendWhileUntil
{
    public function handle(Signal $signal): void
    {
        if ($this->condition->isTrueFor($signal->item->value, $signal->item->key)) {
            $this->consumer->consume($signal->item->value, $signal->item->key);
        } else {
            $signal->forget($this);
        }
        
        $this->next->handle($signal);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($this->isActive) {
                if ($this->condition->isTrueFor($value, $key)) {
                    $this->consumer->consume($value, $key);
                } else {
                    $this->isActive = false;
                }
            }
            
            yield $key => $value;
        }
    }
}