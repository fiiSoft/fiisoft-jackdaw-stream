<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Sending;

use FiiSoft\Jackdaw\Collector\Collector;
use FiiSoft\Jackdaw\Collector\Collectors;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class CollectKeysIn extends BaseOperation
{
    private Collector $collector;
    
    /**
     * @param Collector|\ArrayAccess|\SplHeap|\SplPriorityQueue $collector
     */
    public function __construct($collector)
    {
        $this->collector = Collectors::getAdapter($collector);
    }
    
    public function handle(Signal $signal): void
    {
        $this->collector->add($signal->item->key);
        
        $this->next->handle($signal);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            $this->collector->add($key);
            
            yield $key => $value;
        }
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->collector = Collectors::default();
            
            parent::destroy();
        }
    }
}