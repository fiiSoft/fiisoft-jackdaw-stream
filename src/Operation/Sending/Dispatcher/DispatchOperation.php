<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Sending\Dispatcher;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Internal\StreamPipe;
use FiiSoft\Jackdaw\Operation\Internal\CommonOperationCode;
use FiiSoft\Jackdaw\Operation\Operation;
use FiiSoft\Jackdaw\Stream;

abstract class DispatchOperation extends StreamPipe implements Operation
{
    use CommonOperationCode;
    
    /** @var Handler[] */
    protected array $handlers;
    
    /**
     * @param HandlerReady[] $handlers
     */
    public function __construct(array $handlers)
    {
        if (empty($handlers)) {
            throw InvalidParamException::byName('handlers');
        }
        
        $this->handlers = Handlers::prepare($handlers);
        
        foreach ($this->handlers as $handler) {
            $handler->prepare();
        }
    }
    
    final public function assignStream(Stream $stream): void
    {
        $this->next->assignStream($stream);
    }
    
    final public function streamingFinished(Signal $signal): bool
    {
        foreach ($this->handlers as $handler) {
            $handler->dispatchFinished();
        }
        
        return $this->next->streamingFinished($signal);
    }
    
    final protected function __clone()
    {
        if ($this->next !== null) {
            $this->next = clone $this->next;
            $this->next->setPrev($this);
        }
    }
    
    final public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->isDestroying = true;
            
            $this->handlers = [];
            
            if ($this->next !== null) {
                $this->next->destroy();
                $this->next = null;
            }
            
            if ($this->prev !== null) {
                $this->prev->destroy();
                $this->prev = null;
            }
        }
    }
}