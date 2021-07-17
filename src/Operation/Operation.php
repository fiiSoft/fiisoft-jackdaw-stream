<?php

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Internal\Signal;

interface Operation
{
    public function handle(Signal $signal): void;
    
    public function setNext(Operation $next): Operation;
    
    public function streamingFinished(Signal $signal): void;
    
    public function isLazy(): bool;
}