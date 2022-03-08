<?php

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Internal\Signal;

interface Operation
{
    public function handle(Signal $signal): void;
    
    public function setNext(Operation $next, bool $direct = false): Operation;
    
    public function setPrev(Operation $prev): void;
    
    public function removeFromChain(): Operation;
    
    /**
     * @param Signal $signal
     * @return bool return true to resume stream processing, false otherwise
     */
    public function streamingFinished(Signal $signal): bool;
    
    public function isLazy(): bool;
}