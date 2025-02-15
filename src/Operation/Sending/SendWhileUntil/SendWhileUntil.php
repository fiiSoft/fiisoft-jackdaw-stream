<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Sending\SendWhileUntil;

use FiiSoft\Jackdaw\Condition\Condition;
use FiiSoft\Jackdaw\Condition\ConditionReady;
use FiiSoft\Jackdaw\Condition\Conditions;
use FiiSoft\Jackdaw\Consumer\Consumer;
use FiiSoft\Jackdaw\Consumer\ConsumerReady;
use FiiSoft\Jackdaw\Consumer\Consumers;
use FiiSoft\Jackdaw\Internal\StreamAware;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Stream;

abstract class SendWhileUntil extends BaseOperation
{
    protected Condition $condition;
    protected Consumer $consumer;
    
    protected bool $isActive = true;
    
    /**
     * @param ConditionReady|callable $condition
     * @param ConsumerReady|callable|resource $consumer
     */
    final public function __construct($condition, $consumer)
    {
        $this->condition = Conditions::getAdapter($condition);
        $this->consumer = Consumers::getAdapter($consumer);
    }
    
    final public function assignStream(Stream $stream): void
    {
        parent::assignStream($stream);
        
        if ($this->consumer instanceof StreamAware) {
            $this->consumer->assignStream($stream);
        }
    }
}