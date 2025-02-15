<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Mapping\MapWhileUntil;

use FiiSoft\Jackdaw\Internal\Signal;

final class MapWhile extends MapWhileUntil
{
    public function handle(Signal $signal): void
    {
        if ($this->condition->isTrueFor($signal->item->value, $signal->item->key)) {
            $signal->item->value = $this->mapper->map($signal->item->value, $signal->item->key);
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
                    yield $key => $this->mapper->map($value, $key);
                } else {
                    $this->isActive = false;
                    yield $key => $value;
                }
            } else {
                yield $key => $value;
            }
        }
    }
}