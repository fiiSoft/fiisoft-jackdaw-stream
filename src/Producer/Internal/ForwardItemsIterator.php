<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Internal;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Producer\Producer;

final class ForwardItemsIterator implements Producer
{
    /** @var Item[] */
    private iterable $items;
    
    /**
     * @param Item[] $items
     */
    public function __construct(iterable $items)
    {
        $this->items = $items;
    }
    
    public function feed(Item $item): \Generator
    {
        foreach ($this->items as $x) {
            $item->key = $x->key;
            $item->value = $x->value;
            
            yield;
        }
        
        $this->items = [];
    }
}