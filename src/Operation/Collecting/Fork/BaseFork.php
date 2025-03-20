<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Collecting\Fork;

use FiiSoft\Jackdaw\Discriminator\Discriminator;
use FiiSoft\Jackdaw\Discriminator\DiscriminatorReady;
use FiiSoft\Jackdaw\Discriminator\Discriminators;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\ProcessOperation;
use FiiSoft\Jackdaw\Producer\Producer;

abstract class BaseFork extends ProcessOperation
{
    protected Discriminator $discriminator;
    
    protected ?Producer $producer = null;
    
    /** @var array<string|int, ForkHandler> */
    protected array $handlers = [];
    
    /**
     * @param DiscriminatorReady|callable|array<string|int> $discriminator
     */
    public function __construct($discriminator)
    {
        $this->discriminator = Discriminators::getAdapter($discriminator);
    }
    
    final public function streamingFinished(Signal $signal): bool
    {
        $this->producer = $this->createProducer();
        $signal->restartWith($this->producer, $this->next);
        
        return true;
    }
    
    final protected function finishStreaming(): \Generator
    {
        $producer = $this->createProducer();
        yield from $producer;
        
        $producer->destroy();
        $this->handlers = [];
    }
    
    private function createProducer(): Producer
    {
        return new ForkProducer($this->handlers);
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            parent::destroy();
            
            if ($this->producer !== null) {
                $this->producer->destroy();
                $this->producer = null;
                $this->handlers = [];
            }
        }
    }
}