<?php

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Stream;

interface Operation
{
    public function handle(Signal $signal): void;
    
    public function setNext(Operation $next, bool $direct = false): Operation;
    
    public function setPrev(Operation $prev): void;
    
    public function getNext(): ?Operation;
    
    public function getLast(): Operation;
    
    public function removeFromChain(): Operation;
    
    public function prepend(Operation $operation): void;
    
    /**
     * @return bool return true to resume stream processing, false otherwise
     */
    public function streamingFinished(Signal $signal): bool;
    
    public function assignStream(Stream $stream): void;
}