<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Sending\Dispatcher;

use FiiSoft\Jackdaw\Collector\Collector;
use FiiSoft\Jackdaw\Operation\Sending\Dispatcher\Collector\CollectorPreserveKeys;
use FiiSoft\Jackdaw\Operation\Sending\Dispatcher\Collector\CollectorReindexKeys;

abstract class CollectorAdapter extends PrimitiveHandler
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