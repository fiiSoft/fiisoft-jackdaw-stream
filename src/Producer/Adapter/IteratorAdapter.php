<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Adapter;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Producer\Tech\BaseProducer;

final class IteratorAdapter extends BaseProducer
{
    private \Traversable $iterator;
    
    public function __construct(\Traversable $iterator)
    {
        $this->iterator = $iterator;
    }
    
    public function feed(Item $item): \Generator
    {
        foreach ($this->iterator as $item->key => $item->value) {
            yield;
        }
    }
    
    public function isEmpty(): bool
    {
        return $this->isCountable() && $this->count() === 0;
    }
    
    public function isCountable(): bool
    {
        return $this->iterator instanceof \Countable;
    }
    
    public function count(): int
    {
        if ($this->iterator instanceof \Countable) {
            return $this->iterator->count();
        }
        
        throw new \BadMethodCallException('Cannot count elements in non-countable Traversable iterator');
    }
    
    public function getLast(): ?Item
    {
        return null;
    }
}