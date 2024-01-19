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

final class SendWhile extends BaseOperation
{
    private Condition $condition;
    private Consumer $consumer;
    
    private bool $until, $isActive = true;
    
    /**
     * @param ConditionReady|callable $condition
     * @param ConsumerReady|callable|resource $consumer
     */
    public function __construct($condition, $consumer, bool $until = false)
    {
        $this->condition = Conditions::getAdapter($condition);
        $this->consumer = Consumers::getAdapter($consumer);
        $this->until = $until;
    }
    
    public function handle(Signal $signal): void
    {
        if ($this->until XOR $this->condition->isTrueFor($signal->item->value, $signal->item->key)) {
            $this->consumer->consume($signal->item->value, $signal->item->key);
        } else {
            $signal->forget($this);
        }
        
        $this->next->handle($signal);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($this->isActive) {
                if ($this->until XOR $this->condition->isTrueFor($value, $key)) {
                    $this->consumer->consume($value, $key);
                } else {
                    $this->isActive = false;
                }
            }
            
            yield $key => $value;
        }
    }
}