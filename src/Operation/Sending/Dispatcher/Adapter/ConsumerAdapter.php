<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Sending\Dispatcher\Adapter;

use FiiSoft\Jackdaw\Consumer\Consumer;
use FiiSoft\Jackdaw\Internal\Signal;

final class ConsumerAdapter extends PrimitiveDispatchHandler
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
    
    /**
     * @inheritDoc
     */
    public function handlePair($value, $key): void
    {
        $this->consumer->consume($value, $key);
    }
}