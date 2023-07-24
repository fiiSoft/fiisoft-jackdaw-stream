<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Internal;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Producer\Tech\CountableProducer;

final class ReverseItemsIterator extends CountableProducer
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
    
    public function count(): int
    {
        return \count($this->items);
    }
    
    public function getLast(): ?Item
    {
        return isset($this->items) ? $this->items[0]->copy() : null;
    }
}