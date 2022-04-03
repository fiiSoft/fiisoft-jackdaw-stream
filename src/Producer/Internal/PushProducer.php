<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Internal;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Producer\MultiProducer;

final class PushProducer extends MultiProducer
{
    private ?Item $next = null;
    
    public function seed(Item $item): \Generator
    {
        yield from parent::feed($item);
        yield from $this->feed($item);
    }
    
    public function feed(Item $item): \Generator
    {
        $this->next = yield;
        
        while ($this->next !== null) {
            $item->key = $this->next->key;
            $item->value = $this->next->value;
            yield;
    
            $this->next = yield;
        }
    
        yield from parent::feed($item);
    }
}


