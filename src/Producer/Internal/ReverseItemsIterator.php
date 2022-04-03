<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Internal;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Producer\Producer;

final class ReverseItemsIterator implements Producer
{
    /** @var Item[] */
    private array $items;
    
    /**
     * @param Item[] $items
     */
    public function __construct(array $items)
    {
        $this->items = $items;
    }
    
    public function feed(Item $item): \Generator
    {
        for ($i = \count($this->items) - 1; $i >= 0; --$i) {

            $item->key = $this->items[$i]->key;
            $item->value = $this->items[$i]->value;

            yield;
        }
        
        $this->items = [];
    }
}