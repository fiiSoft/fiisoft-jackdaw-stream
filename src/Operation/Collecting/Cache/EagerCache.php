<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Collecting\Cache;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Collecting\Cache;
use FiiSoft\Jackdaw\Producer\Producers;

final class EagerCache extends Cache
{
    public function handle(Signal $signal): void
    {
        $this->cache->items[] = clone $signal->item;
    }
    
    /**
     * @inheritDoc
     */
    public function buildStream(iterable $stream): iterable
    {
        if (!$this->cache->isFilled) {
            $item = new Item();
            
            foreach ($stream as $item->key => $item->value) {
                $this->cache->items[] = clone $item;
            }
            
            $this->cache->isFilled = true;
        }
        
        yield from $this->cache;
    }
    
    public function streamingFinished(Signal $signal): bool
    {
        if ($signal->isEmpty) {
            $this->cache->isFilled = true;
        }
        
        $signal->restartWith(Producers::getAdapter($this->cache), $this->next);
        
        return true;
    }
    
    public function forceCollectingData(): Cache
    {
        return $this;
    }
}