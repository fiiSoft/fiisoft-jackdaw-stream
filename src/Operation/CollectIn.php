<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Collector\Collector;
use FiiSoft\Jackdaw\Collector\Collectors;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class CollectIn extends BaseOperation
{
    private Collector $collector;
    
    private bool $reindex;
    
    /**
     * @param Collector|\ArrayAccess|\SplHeap|\SplPriorityQueue $collector
     */
    public function __construct($collector, ?bool $reindex = null)
    {
        $this->collector = Collectors::getAdapter($collector);
        $this->reindex = $reindex ?? !$this->collector->canPreserveKeys();
    }
    
    public function handle(Signal $signal): void
    {
        if ($this->reindex) {
            $this->collector->add($signal->item->value);
        } else {
            $this->collector->set($signal->item->key, $signal->item->value);
        }
        
        $this->next->handle($signal);
    }
}