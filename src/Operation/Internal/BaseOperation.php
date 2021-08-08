<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Internal;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Operation;

abstract class BaseOperation implements Operation
{
    protected ?Operation $next = null;
    protected ?Operation $prev = null;
    
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
    
    final public function removeFromChain(): Operation
    {
        $this->prev->setNext($this->next, true);
        return $this->prev;
    }
    
    public function streamingFinished(Signal $signal): void
    {
        $this->next->streamingFinished($signal);
    }
    
    public function isLazy(): bool
    {
        return false;
    }
}