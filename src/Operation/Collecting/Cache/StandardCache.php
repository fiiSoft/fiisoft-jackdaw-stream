<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Collecting\Cache;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Collecting\Cache;

final class StandardCache extends Cache
{
    public function handle(Signal $signal): void
    {
        $this->cache->items[] = clone $signal->item;
        
        $this->next->handle($signal);
    }
    
    /**
     * @inheritDoc
     */
    public function buildStream(iterable $stream): iterable
    {
        if ($this->cache->isFilled) {
            yield from $this->cache;
        } else {
            $this->cache->items = [];
            
            $item = new Item();
            foreach ($stream as $item->key => $item->value) {
                $this->cache->items[] = clone $item;
                
                yield $item->key => $item->value;
            }
            
            $this->cache->isFilled = true;
        }
    }
    
    public function streamingFinished(Signal $signal): bool
    {
        if ($signal->isEmpty) {
            $this->cache->isFilled = true;
        }
        
        return parent::streamingFinished($signal);
    }
    
    public function forceCollectingData(): Cache
    {
        return new EagerCache();
    }
}