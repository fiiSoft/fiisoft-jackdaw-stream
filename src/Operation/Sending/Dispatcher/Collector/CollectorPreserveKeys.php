<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Sending\Dispatcher\Collector;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Sending\Dispatcher\CollectorAdapter;

final class CollectorPreserveKeys extends CollectorAdapter
{
    public function handle(Signal $signal): void
    {
        $this->collector->set($signal->item->key, $signal->item->value);
    }
    
    public function handlePair($value, $key): void
    {
        $this->collector->set($key, $value);
    }
}