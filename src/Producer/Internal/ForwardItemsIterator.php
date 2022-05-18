<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Internal;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Producer\BaseProducer;

final class ForwardItemsIterator extends BaseProducer
{
    /** @var Item[] */
    private iterable $items;
    
    /**
     * @param Item[] $items
     */
    public function __construct(iterable $items = [])
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
    
    public function with(iterable $items): self
    {
        $this->items = $items;
        
        return $this;
    }
}