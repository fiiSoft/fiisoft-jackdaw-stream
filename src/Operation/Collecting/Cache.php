<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Collecting;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Collecting\Cache\CachedData;
use FiiSoft\Jackdaw\Operation\Collecting\Cache\StandardCache;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Operation\Internal\Detachable;
use FiiSoft\Jackdaw\Producer\Producers;

abstract class Cache extends BaseOperation implements Detachable
{
    protected CachedData $cache;
    
    final public static function create(): self
    {
        return new StandardCache();
    }
    
    final protected function __construct()
    {
        $this->cache = new CachedData();
    }
    
    final public function streamingStart(Signal $signal): void
    {
        if ($this->cache->isFilled) {
            $signal->restartWith(Producers::getAdapter($this->cache), $this->next);
        } else {
            $this->cache->items = [];
        }
        
        $this->next->streamingStart($signal);
    }
    
    final public function destroy(): void
    {
        if (!$this->isDestroying) {
            parent::destroy();
            
            $this->cache->items = [];
            $this->cache->isFilled = true;
        }
    }
    
    final public function makeDetachedCopy(): self
    {
        return new $this();
    }
    
    abstract public function forceCollectingData(): self;
}