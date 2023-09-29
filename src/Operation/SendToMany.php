<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Consumer\Consumer;
use FiiSoft\Jackdaw\Consumer\ConsumerReady;
use FiiSoft\Jackdaw\Consumer\Consumers;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class SendToMany extends BaseOperation
{
    /** @var Consumer[] */
    private array $consumers = [];
    
    /**
     * @param ConsumerReady|callable|resource $consumers
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
    
    public function getConsumers(): array
    {
        return $this->consumers;
    }
    
    public function addConsumers(Consumer ...$others): void
    {
        $this->consumers = \array_merge($this->consumers, $others);
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->consumers = [];
            
            parent::destroy();
        }
    }
}