<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Internal;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Producer\Tech\BaseProducer;

final class ForwardItemsIterator extends BaseProducer
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
    
    public function getIterator(): \Generator
    {
        foreach ($this->items as $x) {
            yield $x->key => $x->value;
        }
        
        $this->items = [];
    }
    
    public function with(array $items): self
    {
        $this->items = $items;
        
        return $this;
    }
    
    public function destroy(): void
    {
        $this->items = [];
    }
}