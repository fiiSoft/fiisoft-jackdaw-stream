<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Sending\Dispatcher\Adapter;

use FiiSoft\Jackdaw\Collector\Collector;
use FiiSoft\Jackdaw\Operation\Sending\Dispatcher\Adapter\Collector\CollectorPreserveKeys;
use FiiSoft\Jackdaw\Operation\Sending\Dispatcher\Adapter\Collector\CollectorReindexKeys;

abstract class CollectorAdapter extends PrimitiveDispatchHandler
{
    protected Collector $collector;
    
    final public static function create(Collector $collector): self
    {
        return $collector->canPreserveKeys()
            ? new CollectorPreserveKeys($collector)
            : new CollectorReindexKeys($collector);
    }
    
    final protected function __construct(Collector $collector)
    {
        $this->collector = $collector;
    }
}