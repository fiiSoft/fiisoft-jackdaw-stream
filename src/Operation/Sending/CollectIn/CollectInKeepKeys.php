<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Sending\CollectIn;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Sending\CollectIn;

final class CollectInKeepKeys extends CollectIn
{
    public function handle(Signal $signal): void
    {
        $this->collector->set($signal->item->key, $signal->item->value);
        
        $this->next->handle($signal);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            $this->collector->set($key, $value);
            
            yield $key => $value;
        }
    }
}