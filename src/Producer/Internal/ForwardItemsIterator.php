<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Internal;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Producer\Tech\CountableProducer;

final class ForwardItemsIterator extends CountableProducer
{
    /** @var Item[] */
    private array $items;
    
    /**
     * @param Item[] $items
     */
    public function __construct(array $items = [])
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
    
    public function with(array $items): self
    {
        $this->items = $items;
        
        return $this;
    }
    
    public function count(): int
    {
        return \count($this->items);
    }
    
    public function getLast(): ?Item
    {
        if (empty($this->items)) {
            return null;
        }
        
        $last = \array_key_last($this->items);
        
        return $this->items[$last]->copy();
    }
    
    public function destroy(): void
    {
        $this->items = [];
    }
}