<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Internal;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Operation;

final class Initial implements Operation
{
    /** @var Operation|null */
    private $next = null;
    
    public function handle(Signal $signal)
    {
        $this->next->handle($signal);
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
    
    public function setPrev(Operation $prev)
    {
        throw new \LogicException('It should never happen (Inital::setPrev)');
    }
    
    public function removeFromChain(): Operation
    {
        return $this->next;
    }
    
    public function streamingFinished(Signal $signal)
    {
        $this->next->streamingFinished($signal);
    }
    
    public function isLazy(): bool
    {
        return false;
    }
}