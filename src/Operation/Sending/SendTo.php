<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Sending;

use FiiSoft\Jackdaw\Consumer\Consumer;
use FiiSoft\Jackdaw\Consumer\ConsumerReady;
use FiiSoft\Jackdaw\Consumer\Consumers;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Internal\StreamAware;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Stream;

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
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            $this->consumer->consume($value, $key);
            
            yield $key => $value;
        }
    }
    
    public function consumer(): Consumer
    {
        return $this->consumer;
    }
    
    public function createSendToMany(SendTo $other): SendToMany
    {
        return new SendToMany($this->consumer, $other->consumer);
    }
    
    public function assignStream(Stream $stream): void
    {
        parent::assignStream($stream);
        
        if ($this->consumer instanceof StreamAware) {
            $this->consumer->assignStream($stream);
        }
    }
}