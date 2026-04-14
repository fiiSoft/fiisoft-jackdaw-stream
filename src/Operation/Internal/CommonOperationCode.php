<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Internal;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Operation;
use FiiSoft\Jackdaw\Stream;

trait CommonOperationCode
{
    protected ?Operation $next = null;
    protected ?Operation $prev = null;
    
    protected bool $isDestroying = false;
    
    final public function setNext(Operation $next, bool $direct = false): Operation
    {
        if ($this->next !== null && !$direct) {
            $next->setNext($this->next);
        }
        
        $this->next = $next;
        $next->setPrev($this);
        
        return $next;
    }
    
    final public function setPrev(Operation $prev): void
    {
        $this->prev = $prev;
    }
    
    final public function prepend(Operation $operation): void
    {
        $this->prev->setNext($operation, true);
        
        $operation->setNext($this);
    }
    
    final public function removeFromChain(): Operation
    {
        $this->prev->setNext($this->next, true);
        
        return $this->prev;
    }
    
    final public function getNext(): ?Operation
    {
        return $this->next;
    }
    
    final public function getPrev(): ?Operation
    {
        return $this->prev;
    }
    
    final public function getLast(): Operation
    {
        return $this->next->getLast();
    }
    
    public function assignStream(Stream $stream): void
    {
        $this->next->assignStream($stream);
    }
    
    public function streamingStart(Signal $signal): void
    {
        $this->next->streamingStart($signal);
    }
    
    public function streamingFinished(Signal $signal): bool
    {
        return $this->next->streamingFinished($signal);
    }
    
    public function resume(): void
    {
        $this->next->resume();
    }
    
    public function prepare(): void
    {
        $this->next->prepare();
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->isDestroying = true;
            
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