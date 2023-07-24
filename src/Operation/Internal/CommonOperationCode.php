<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Internal;

use FiiSoft\Jackdaw\Operation\Operation;
use FiiSoft\Jackdaw\Operation\Reverse;
use FiiSoft\Jackdaw\Operation\Shuffle;
use FiiSoft\Jackdaw\Operation\Unique;

trait CommonOperationCode
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
    
    final public function prepend(Operation $operation): void
    {
        if ($this->prev !== null) {
            if ($this->prev instanceof Shuffle
                || $this->prev instanceof Reverse
                || $this->prev instanceof Unique
                || $this->prev instanceof SortingOperation
            ) {
                $this->prev->prepend($operation);
            } else {
                $this->prev->setNext($operation, true);
                $operation->setNext($this);
            }
        }
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
    
    final public function getLast(): Operation
    {
        return $this->next !== null ? $this->next->getLast() : $this;
    }
}