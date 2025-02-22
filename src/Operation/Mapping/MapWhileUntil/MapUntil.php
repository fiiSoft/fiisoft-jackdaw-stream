<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Mapping\MapWhileUntil;

use FiiSoft\Jackdaw\Internal\Signal;

final class MapUntil extends MapWhileUntil
{
    public function handle(Signal $signal): void
    {
        if ($this->condition->isAllowed($signal->item->value, $signal->item->key)) {
            $signal->forget($this);
        } else {
            $signal->item->value = $this->mapper->map($signal->item->value, $signal->item->key);
        }
        
        $this->next->handle($signal);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($this->isActive) {
                if ($this->condition->isAllowed($value, $key)) {
                    $this->isActive = false;
                    yield $key => $value;
                } else {
                    yield $key => $this->mapper->map($value, $key);
                }
            } else {
                yield $key => $value;
            }
        }
    }
}