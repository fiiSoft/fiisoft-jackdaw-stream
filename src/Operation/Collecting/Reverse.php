<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Collecting;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Producer\Internal\ReverseItemsIterator;

final class Reverse extends BaseOperation
{
    /** @var Item[] */
    private array $items = [];
    
    public function handle(Signal $signal): void
    {
        $this->items[] = clone $signal->item;
    }
    
    public function buildStream(iterable $stream): iterable
    {
        $item = new Item();
        
        foreach ($stream as $item->key => $item->value) {
            $this->items[] = clone $item;
        }
        
        if (empty($this->items)) {
            return [];
        }
        
        yield from new ReverseItemsIterator($this->items);
        
        $this->items = [];
    }
    
    public function streamingFinished(Signal $signal): bool
    {
        if (empty($this->items)) {
            return parent::streamingFinished($signal);
        }
        
        $signal->restartWith(new ReverseItemsIterator($this->items), $this->next);
        $this->items = [];
        
        return true;
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->items = [];
            
            parent::destroy();
        }
    }
}