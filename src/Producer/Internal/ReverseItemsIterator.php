<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Internal;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Producer\Tech\BaseProducer;

final class ReverseItemsIterator extends BaseProducer
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
    
    public function getIterator(): \Generator
    {
        if ($this->reindex) {
            for ($index = -1, $i = \count($this->items) - 1; $i >= 0; --$i) {
                yield ++$index => $this->items[$i]->value;
            }
        } else {
            for ($i = \count($this->items) - 1; $i >= 0; --$i) {
                yield $this->items[$i]->key => $this->items[$i]->value;
            }
        }
        
        $this->items = [];
    }
    
    public function destroy(): void
    {
        $this->items = [];
    }
}