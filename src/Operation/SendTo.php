<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Consumer\Consumer;
use FiiSoft\Jackdaw\Consumer\ConsumerReady;
use FiiSoft\Jackdaw\Consumer\Consumers;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class SendTo extends BaseOperation
{
    private Consumer $consumer;
    
    /**
     * @param ConsumerReady|callable|resource $consumer
     */
    public function __construct($consumer)
    {
        $this->consumer = Consumers::getAdapter($consumer);
    }
    
    public function handle(Signal $signal): void
    {
        $this->consumer->consume($signal->item->value, $signal->item->key);
    
        $this->next->handle($signal);
    }
    
    public function consumer(): Consumer
    {
        return $this->consumer;
    }
    
    public function createSendToMany(SendTo $other): SendToMany
    {
        return new SendToMany($this->consumer, $other->consumer);
    }
}