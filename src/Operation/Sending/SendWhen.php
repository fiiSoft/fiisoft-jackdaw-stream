<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Sending;

use FiiSoft\Jackdaw\Consumer\Consumer;
use FiiSoft\Jackdaw\Consumer\ConsumerReady;
use FiiSoft\Jackdaw\Consumer\Consumers;
use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\FilterReady;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class SendWhen extends BaseOperation
{
    private Filter $condition;
    
    private Consumer $consumer;
    private ?Consumer $elseConsumer = null;
    
    /**
     * @param FilterReady|callable|array<string|int, mixed>|scalar $condition
     * @param ConsumerReady|callable|resource $consumer
     * @param ConsumerReady|callable|resource|null $elseConsumer
     */
    public function __construct($condition, $consumer, $elseConsumer = null)
    {
        $this->condition = Filters::getAdapter($condition);
        $this->consumer = Consumers::getAdapter($consumer);
    
        if ($elseConsumer !== null) {
            $this->elseConsumer = Consumers::getAdapter($elseConsumer);
        }
    }
    
    public function handle(Signal $signal): void
    {
        if ($this->condition->isAllowed($signal->item->value, $signal->item->key)) {
            $this->consumer->consume($signal->item->value, $signal->item->key);
        } elseif ($this->elseConsumer !== null) {
            $this->elseConsumer->consume($signal->item->value, $signal->item->key);
        }
        
        $this->next->handle($signal);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($this->condition->isAllowed($value, $key)) {
                $this->consumer->consume($value, $key);
            } elseif ($this->elseConsumer !== null) {
                $this->elseConsumer->consume($value, $key);
            }
            
            yield $key => $value;
        }
    }
}