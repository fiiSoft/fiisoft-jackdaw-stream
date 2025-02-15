<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Internal\Pipe;

use FiiSoft\Jackdaw\Exception\ImpossibleSituationException;
use FiiSoft\Jackdaw\Internal\ProtectedCloning;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Operation;
use FiiSoft\Jackdaw\Stream;

final class Initial extends ProtectedCloning implements Operation
{
    private Operation $next;
    
    public function __construct()
    {
        $this->next = new Ending($this);
    }
    
    public function handle(Signal $signal): void
    {
        $this->next->handle($signal);
    }
    
    public function assignStream(Stream $stream): void
    {
        $this->next->assignStream($stream);
    }
    
    public function setNext(Operation $next, bool $direct = false): Operation
    {
        if (!$direct) {
            $next->setNext($this->next);
        }
        
        $this->next = $next;
        $next->setPrev($this);
        
        return $next;
    }
    
    public function setPrev(Operation $prev): void
    {
        throw ImpossibleSituationException::called(__METHOD__);
    }
    
    public function prepend(Operation $operation): void
    {
        throw ImpossibleSituationException::called(__METHOD__);
    }
    
    public function getNext(): Operation
    {
        return $this->next;
    }
    
    public function getPrev(): ?Operation
    {
        return null;
    }
    
    public function getLast(): Operation
    {
        return $this->next->getLast();
    }
    
    public function removeFromChain(): Operation
    {
        return $this->next instanceof Ending ? $this : $this->next;
    }
    
    public function streamingFinished(Signal $signal): bool
    {
        return $this->next->streamingFinished($signal);
    }
    
    public function __clone()
    {
        $this->next = clone $this->next;
        $this->next->setPrev($this);
    }
    
    public function destroy(): void
    {
        $this->next->destroy();
    }
    
    public function buildStream(iterable $stream): iterable
    {
        return $stream;
    }
    
    public function resume(): void
    {
        $this->next->resume();
    }
}