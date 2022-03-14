<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Condition\Condition;
use FiiSoft\Jackdaw\Condition\Conditions;
use FiiSoft\Jackdaw\Consumer\Consumer;
use FiiSoft\Jackdaw\Consumer\Consumers;
use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Predicate\Predicate;

final class SendWhen extends BaseOperation
{
    private Condition $condition;
    
    private Consumer $consumer;
    private ?Consumer $elseConsumer = null;
    
    /**
     * @param Condition|Predicate|Filter|callable $condition
     * @param Consumer|callable|resource $consumer
     * @param Consumer|callable|resource|null $elseConsumer
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
}