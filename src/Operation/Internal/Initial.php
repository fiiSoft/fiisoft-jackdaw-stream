<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Internal;

use FiiSoft\Jackdaw\Internal\ProtectedCloning;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Operation;
use FiiSoft\Jackdaw\Stream;

final class Initial extends ProtectedCloning implements Operation
{
    private ?Operation $next = null;
    
    public function __construct()
    {
        $this->setNext(new Ending());
    }
    
    public function handle(Signal $signal): void
    {
        $this->next->handle($signal);
    }
    
    public function assignStream(Stream $stream): void
    {
        if ($this->next !== null) {
            $this->next->assignStream($stream);
        }
    }
    
    public function setNext(Operation $next, bool $direct = false): Operation
    {
        if ($this->next !== null && !$direct) {
            $next->setNext($this->next);
        }
        
        $this->next = $next;
        $next->setPrev($this);
        
        return $next;
    }
    
    public function setPrev(Operation $prev): void
    {
        throw new \LogicException('It should never happen (Inital::setPrev)');
    }
    
    public function prepend(Operation $operation): void
    {
        throw new \LogicException('It should never happen (Inital::prepend)');
    }
    
    public function getNext(): ?Operation
    {
        return $this->next;
    }
    
    public function getPrev(): ?Operation
    {
        return null;
    }
    
    public function getLast(): Operation
    {
        return $this->next !== null ? $this->next->getLast() : $this;
    }
    
    public function removeFromChain(): Operation
    {
        return $this->next;
    }
    
    public function streamingFinished(Signal $signal): bool
    {
        return $this->next->streamingFinished($signal);
    }
    
    protected function __clone()
    {
        if ($this->next !== null) {
            $this->next = clone $this->next;
            $this->next->setPrev($this);
        }
    }
    
    public function destroy(): void
    {
        $this->next = null;
    }
}