<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Collecting;

use FiiSoft\Jackdaw\Discriminator\DiscriminatorReady;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Collecting\Fork\BaseFork;
use FiiSoft\Jackdaw\Operation\Collecting\Fork\ForkHandler;
use FiiSoft\Jackdaw\Operation\Collecting\Fork\ForkHandlerFactory;
use FiiSoft\Jackdaw\Operation\Exception\OperationExceptionFactory;

final class ForkMatch extends BaseFork
{
    private ?ForkHandler $prototype = null;
    
    /**
     * @param DiscriminatorReady|callable|array<string|int> $discriminator
     * @param array<string|int, ForkReady> $handlers
     */
    public function __construct($discriminator, array $handlers, ?ForkReady $prototype = null)
    {
        parent::__construct($discriminator);
        
        if (empty($handlers)) {
            throw OperationExceptionFactory::forkMatchHandlersCannotBeEmpty();
        }
        
        foreach ($handlers as $key => $handler) {
            $this->handlers[$key] = ForkHandlerFactory::adaptHandler($handler);
        }
        
        if ($prototype !== null) {
            $this->prototype = ForkHandlerFactory::adaptPrototype($prototype);
        }
    }
    
    public function handle(Signal $signal): void
    {
        $classifier = $this->discriminator->classify($signal->item->value, $signal->item->key);
        
        if (isset($this->handlers[$classifier])) {
            $handler = $this->handlers[$classifier];
        } elseif ($this->prototype !== null) {
            $handler = $this->prototype->create();
            $this->handlers[$classifier] = $handler;
        } else {
            throw OperationExceptionFactory::handlerIsNotDefined($classifier);
        }
        
        $handler->accept($signal->item->value, $signal->item->key);
    }
    
    /**
     * @inheritDoc
     */
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            $classifier = $this->discriminator->classify($value, $key);
            
            if (isset($this->handlers[$classifier])) {
                $handler = $this->handlers[$classifier];
            } elseif ($this->prototype !== null) {
                $handler = $this->prototype->create();
                $this->handlers[$classifier] = $handler;
            } else {
                throw OperationExceptionFactory::handlerIsNotDefined($classifier);
            }
            
            $handler->accept($value, $key);
        }
        
        yield from $this->finishStreaming();
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            parent::destroy();
            
            if ($this->prototype !== null) {
                $this->prototype->destroy();
                $this->prototype = null;
            }
        }
    }
}