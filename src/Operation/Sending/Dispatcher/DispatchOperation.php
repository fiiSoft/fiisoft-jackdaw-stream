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
    
    /** @var StreamPipe[] */
    protected array $streams = [];
    
    /**
     * @param HandlerReady[] $handlers
     */
    public function __construct(array $handlers)
    {
        if (empty($handlers)) {
            throw InvalidParamException::byName('handlers');
        }
        
        $this->handlers = Handlers::prepare($handlers);
        
        foreach ($handlers as $handler) {
            if ($handler instanceof StreamPipe) {
                
                $id = \spl_object_id($handler);
                
                if (!isset($this->streams[$id])) {
                    $this->streams[$id] = $handler;
                    $handler->prepareSubstream(false);
                }
            }
        }
    }
    
    final public function assignStream(Stream $stream): void
    {
        if ($this->next !== null) {
            $this->next->assignStream($stream);
        }
    }
    
    final public function streamingFinished(Signal $signal): bool
    {
        while (!empty($this->streams)) {
            foreach ($this->streams as $key => $stream) {
                if (!$stream->continueIteration()) {
                    unset($this->streams[$key]);
                }
            }
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
            $this->streams = [];
            
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