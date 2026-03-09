<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Special\IterateOver;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Special\IterateOver;

final class ZeroArgIterateOver extends IterateOver
{
    public function handle(Signal $signal): void
    {
        $item = $signal->item;
        
        foreach (($this->callable)() as $item->key => $item->value) {
            $this->next->handle($signal);
        }
    }
    
    /**
     * @inheritDoc
     */
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $_) {
            yield from ($this->callable)();
        }
    }
}