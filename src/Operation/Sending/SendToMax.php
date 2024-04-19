<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Sending;

use FiiSoft\Jackdaw\Consumer\Consumer;
use FiiSoft\Jackdaw\Consumer\ConsumerReady;
use FiiSoft\Jackdaw\Consumer\Consumers;
use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Internal\StreamAware;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Stream;

final class SendToMax extends BaseOperation
{
    private Consumer $consumer;
    
    private int $times;
    private int $count = 0;
    
    /**
     * @param int $times how many times consumer can be called
     * @param ConsumerReady|callable|resource $consumer
     */
    public function __construct(int $times, $consumer)
    {
        if ($times < 1) {
            throw InvalidParamException::describe('times', $times);
        }
        
        $this->consumer = Consumers::getAdapter($consumer);
        $this->times = $times;
    }
    
    public function handle(Signal $signal): void
    {
        if ($this->count < $this->times) {
            ++$this->count;
            $this->consumer->consume($signal->item->value, $signal->item->key);
        } else {
            $signal->forget($this);
        }
        
        $this->next->handle($signal);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($this->count < $this->times) {
                ++$this->count;
                
                $this->consumer->consume($value, $key);
            }
            
            yield $key => $value;
        }
    }
    
    public function assignStream(Stream $stream): void
    {
        parent::assignStream($stream);
        
        if ($this->consumer instanceof StreamAware) {
            $this->consumer->assignStream($stream);
        }
    }
}