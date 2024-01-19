<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Sending\StoreIn;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Sending\StoreIn;

final class StoreInKeepKeys extends StoreIn
{
    public function handle(Signal $signal): void
    {
        $this->buffer[$signal->item->key] = $signal->item->value;
        
        $this->next->handle($signal);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            $this->buffer[$key] = $value;
            
            yield $key => $value;
        }
    }
}