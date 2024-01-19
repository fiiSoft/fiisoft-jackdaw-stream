<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Mapping\Aggregate;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Mapping\Aggregate;

final class SingleAggregate extends Aggregate
{
    public function handle(Signal $signal): void
    {
        $item = $signal->item;
        
        if (isset($this->keys[$item->key])) {
            $item->value = [$item->key => $item->value];
            $item->key = $this->index++;
            
            $this->next->handle($signal);
        }
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if (isset($this->keys[$key])) {
                yield $this->index++ => [$key => $value];
            }
        }
    }
}