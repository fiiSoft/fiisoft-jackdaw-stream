<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Sending;

use FiiSoft\Jackdaw\Collector\Collector;
use FiiSoft\Jackdaw\Collector\Collectors;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Operation\Sending\CollectIn\CollectInKeepKeys;
use FiiSoft\Jackdaw\Operation\Sending\CollectIn\CollectInReindexKeys;

abstract class CollectIn extends BaseOperation
{
    protected Collector $collector;
    
    /**
     * @param Collector|\ArrayAccess<string|int, mixed>|\SplHeap<mixed>|\SplPriorityQueue<int, mixed> $collector
     */
    final public static function create($collector, ?bool $reindex = null): self
    {
        $collector = Collectors::getAdapter($collector);
        
        return $reindex ?? !$collector->canPreserveKeys()
            ? new CollectInReindexKeys($collector)
            : new CollectInKeepKeys($collector);
    }
    
    final protected function __construct(Collector $collector)
    {
        $this->collector = $collector;
    }
    
    final public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->collector = Collectors::default();
            
            parent::destroy();
        }
    }
}