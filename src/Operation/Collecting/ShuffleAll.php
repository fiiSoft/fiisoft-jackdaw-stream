<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Collecting;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Special\Shuffle;

final class ShuffleAll extends Shuffle
{
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
        
        \shuffle($this->items);
        
        foreach ($this->items as $item) {
            yield $item->key => $item->value;
        }
        
        $this->reset();
    }
    
    public function mergedWith(Shuffle $other): Shuffle
    {
        return self::create();
    }
    
    protected function reset(): void
    {
        $this->items = [];
    }
}