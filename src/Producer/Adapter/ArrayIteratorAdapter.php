<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Adapter;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Producer\Tech\CountableProducer;

final class ArrayIteratorAdapter extends CountableProducer
{
    private \ArrayIterator $iterator;
    
    public function __construct(\ArrayIterator $iterator)
    {
        $this->iterator = $iterator;
    }
    
    public function feed(Item $item): \Generator
    {
        foreach ($this->iterator as $item->key => $item->value) {
            yield;
        }
    }
    
    public function count(): int
    {
        return $this->iterator->count();
    }
    
    public function getLast(): ?Item
    {
        if ($this->iterator->count() > 0) {
            $data = $this->iterator->getArrayCopy();
            $last = \array_key_last($data);
            
            return new Item($last, $data[$last]);
        }
        
        return null;
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->iterator = new \ArrayIterator();
            
            parent::destroy();
        }
    }
}