<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Consumer\Consumer;
use FiiSoft\Jackdaw\Consumer\Consumers;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class SendTo extends BaseOperation
{
    /** @var Consumer */
    private $consumer;
    
    /**
     * @param Consumer|callable $consumer
     */
    public function __construct($consumer)
    {
        $this->consumer = Consumers::getAdapter($consumer);
    }
    
    public function handle(Signal $signal)
    {
        $this->consumer->consume($signal->item->value, $signal->item->key);
    
        $this->next->handle($signal);
    }
}