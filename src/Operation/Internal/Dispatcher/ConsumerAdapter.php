<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Internal\Dispatcher;

use FiiSoft\Jackdaw\Consumer\Consumer;
use FiiSoft\Jackdaw\Internal\Signal;

final class ConsumerAdapter implements Handler
{
    private Consumer $consumer;
    
    public function __construct(Consumer $consumer)
    {
        $this->consumer = $consumer;
    }
    
    public function handle(Signal $signal): void
    {
        $this->consumer->consume($signal->item->value, $signal->item->key);
    }
}