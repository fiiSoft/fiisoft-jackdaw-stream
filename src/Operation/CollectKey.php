<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Collector\Collector;
use FiiSoft\Jackdaw\Collector\Collectors;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class CollectKey extends BaseOperation
{
    private Collector $collector;
    
    /**
     * @param Collector|\ArrayAccess $collector
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
}