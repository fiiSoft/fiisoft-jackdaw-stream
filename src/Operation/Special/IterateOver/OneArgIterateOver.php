<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Special\IterateOver;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Special\IterateOver;

final class OneArgIterateOver extends IterateOver
{
    public function handle(Signal $signal): void
    {
        $item = $signal->item;
        
        foreach (($this->callable)($item->value) as $item->key => $item->value) {
            $this->next->handle($signal);
        }
    }
    
    /**
     * @inheritDoc
     */
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $value) {
            yield from ($this->callable)($value);
        }
    }
}