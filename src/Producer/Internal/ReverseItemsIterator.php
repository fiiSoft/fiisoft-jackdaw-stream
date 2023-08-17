<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Internal;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Producer\Tech\CountableProducer;

final class ReverseItemsIterator extends CountableProducer
{
    /** @var Item[] */
    private array $items;
    
    private bool $reindex;
    
    /**
     * @param Item[] $items
     */
    public function __construct(array $items, bool $reindex = false)
    {
        $this->items = $items;
        $this->reindex = $reindex;
    }
    
    public function feed(Item $item): \Generator
    {
        if ($this->reindex) {
            $index = 0;
            
            for ($i = \count($this->items) - 1; $i >= 0; --$i) {
                
                $item->key = $index++;
                $item->value = $this->items[$i]->value;
                
                yield;
            }
        } else {
            for ($i = \count($this->items) - 1; $i >= 0; --$i) {
                
                $item->key = $this->items[$i]->key;
                $item->value = $this->items[$i]->value;
                
                yield;
            }
        }
        
        
        $this->items = [];
    }
    
    public function count(): int
    {
        return \count($this->items);
    }
    
    public function getLast(): ?Item
    {
        if (isset($this->items[0])) {
            $last = $this->items[0]->copy();
            
            if ($this->reindex) {
                $last->key = $this->count() - 1;
            }
            
            return $last;
        }
        
        return null;
    }
    
    public function destroy(): void
    {
        $this->items = [];
    }
}