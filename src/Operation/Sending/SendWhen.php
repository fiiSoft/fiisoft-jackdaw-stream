<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Sending;

use FiiSoft\Jackdaw\Condition\Condition;
use FiiSoft\Jackdaw\Condition\ConditionReady;
use FiiSoft\Jackdaw\Condition\Conditions;
use FiiSoft\Jackdaw\Consumer\Consumer;
use FiiSoft\Jackdaw\Consumer\ConsumerReady;
use FiiSoft\Jackdaw\Consumer\Consumers;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class SendWhen extends BaseOperation
{
    private Condition $condition;
    
    private Consumer $consumer;
    private ?Consumer $elseConsumer = null;
    
    /**
     * @param ConditionReady|callable $condition
     * @param ConsumerReady|callable|resource $consumer
     * @param ConsumerReady|callable|resource|null $elseConsumer
     */
    public function __construct($condition, $consumer, $elseConsumer = null)
    {
        $this->condition = Conditions::getAdapter($condition);
        $this->consumer = Consumers::getAdapter($consumer);
    
        if ($elseConsumer !== null) {
            $this->elseConsumer = Consumers::getAdapter($elseConsumer);
        }
    }
    
    public function handle(Signal $signal): void
    {
        $item = $signal->item;
        
        if ($this->condition->isTrueFor($item->value, $item->key)) {
            $this->consumer->consume($item->value, $item->key);
        } elseif ($this->elseConsumer !== null) {
            $this->elseConsumer->consume($item->value, $item->key);
        }
        
        $this->next->handle($signal);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($this->condition->isTrueFor($value, $key)) {
                $this->consumer->consume($value, $key);
            } elseif ($this->elseConsumer !== null) {
                $this->elseConsumer->consume($value, $key);
            }
            
            yield $key => $value;
        }
    }
}