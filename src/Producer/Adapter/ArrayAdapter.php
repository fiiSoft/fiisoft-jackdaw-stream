<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Adapter;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Producer\Tech\CountableProducer;

final class ArrayAdapter extends CountableProducer
{
    private array $source;
    
    public function __construct(array $source)
    {
        $this->source = $source;
    }
    
    public function feed(Item $item): \Generator
    {
        foreach ($this->source as $item->key => $item->value) {
            yield;
        }
    }
    
    public function count(): int
    {
        return \count($this->source);
    }
    
    public function getLast(): ?Item
    {
        if (empty($this->source)) {
            return null;
        }
        
        $last = \array_key_last($this->source);
        
        return new Item($last, $this->source[$last]);
    }
}