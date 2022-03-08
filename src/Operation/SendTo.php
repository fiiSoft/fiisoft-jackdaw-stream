<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Consumer\Consumer;
use FiiSoft\Jackdaw\Consumer\Consumers;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class SendTo extends BaseOperation
{
    /** @var Consumer[] */
    private array $consumers = [];
    
    /**
     * @param Consumer|callable $consumers
     */
    public function __construct(...$consumers)
    {
        foreach ($consumers as $consumer) {
            $this->consumers[] = Consumers::getAdapter($consumer);
        }
    }
    
    public function handle(Signal $signal): void
    {
        foreach ($this->consumers as $consumer) {
            $consumer->consume($signal->item->value, $signal->item->key);
        }
    
        $this->next->handle($signal);
    }
    
    public function mergeWith(SendTo $other): void
    {
        foreach ($other->consumers as $consumer) {
            $this->consumers[] = $consumer;
        }
    }
}