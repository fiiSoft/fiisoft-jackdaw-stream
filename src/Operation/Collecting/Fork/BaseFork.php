<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Collecting\Fork;

use FiiSoft\Jackdaw\Discriminator\Discriminator;
use FiiSoft\Jackdaw\Discriminator\DiscriminatorReady;
use FiiSoft\Jackdaw\Discriminator\Discriminators;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\ForkReady;
use FiiSoft\Jackdaw\Operation\Internal\ProcessOperation;
use FiiSoft\Jackdaw\Producer\Producer;

abstract class BaseFork extends ProcessOperation
{
    protected Discriminator $discriminator;
    
    /** @var array<string|int, ForkHandler> */
    protected array $handlers = [];
    
    /** @var array<string|int, ForkHandler> */
    private array $prototypes = [];
    
    private ?Producer $producer = null;
    
    /**
     * @param DiscriminatorReady|callable|array<string|int> $discriminator
     * @param array<string|int, ForkReady> $handlers
     */
    public function __construct($discriminator, array $handlers = [])
    {
        $this->discriminator = Discriminators::getAdapter($discriminator);
        
        foreach ($handlers as $key => $handler) {
            $this->prototypes[$key] = ForkHandlerFactory::getAdapter($handler);
        }
    }
    
    public function prepare(): void
    {
        parent::prepare();
        
        $this->handlers = [];
        
        foreach ($this->prototypes as $name => $prototype) {
            $this->handlers[$name] = $prototype->create();
        }
    }
    
    final public function streamingFinished(Signal $signal): bool
    {
        $this->producer = $this->createProducer();
        $signal->restartWith($this->producer, $this->next);
        
        return true;
    }
    
    final protected function finishStreaming(): \Generator
    {
        yield from $this->createProducer();
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