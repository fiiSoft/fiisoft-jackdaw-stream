<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Internal;

use ArrayAccess;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Producer\Producer;

final class ReverseItemsIterator implements Producer
{
    /** @var ArrayAccess|array|Item[] */
    private $items;
    
    /**
     * @param ArrayAccess|array|Item[] $items
     */
    public function __construct($items)
    {
        if (\is_array($items) || $items instanceof \ArrayAccess) {
            $this->items = $items;
        } else {
            throw new \InvalidArgumentException('Invalid param items');
        }
    }
    
    public function feed(Item $item): \Generator
    {
        for ($i = \count($this->items) - 1; $i >= 0; --$i) {
            $x = $this->items[$i];
            
            $item->key = $x->key;
            $item->value = $x->value;
    
            yield;
        }
        
        $this->items = [];
    }
}