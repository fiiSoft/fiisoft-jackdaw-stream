<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Consumer\Consumer;
use FiiSoft\Jackdaw\Consumer\Consumers;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class SendToMax extends BaseOperation
{
    private Consumer $consumer;
    
    private int $times;
    private int $count = 0;
    
    /**
     * @param int $times how many times consumer can be called
     * @param Consumer|callable|resource $consumer
     */
    public function __construct(int $times, $consumer)
    {
        if ($times < 1) {
            throw new \InvalidArgumentException('Invalid param times');
        }
        
        $this->consumer = Consumers::getAdapter($consumer);
        $this->times = $times;
    }
    
    public function handle(Signal $signal): void
    {
        if ($this->count < $this->times) {
            ++$this->count;
            $this->consumer->consume($signal->item->value, $signal->item->key);
        }
        
        $this->next->handle($signal);
    }
}