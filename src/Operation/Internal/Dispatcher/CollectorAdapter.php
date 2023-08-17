<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Internal\Dispatcher;

use FiiSoft\Jackdaw\Collector\Collector;
use FiiSoft\Jackdaw\Internal\Signal;

final class CollectorAdapter implements Handler
{
    private Collector $collector;
    
    private bool $preserveKeys;
    
    public function __construct(Collector $collector)
    {
        $this->collector = $collector;
        $this->preserveKeys = $collector->canPreserveKeys();
    }
    
    public function handle(Signal $signal): void
    {
        if ($this->preserveKeys) {
            $this->collector->set($signal->item->key, $signal->item->value);
        } else {
            $this->collector->add($signal->item->value);
        }
    }
}