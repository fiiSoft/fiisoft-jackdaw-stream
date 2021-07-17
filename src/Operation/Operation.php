<?php

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Internal\Signal;

interface Operation
{
    /**
     * @param Signal $signal
     * @return void
     */
    public function handle(Signal $signal);
    
    public function setNext(Operation $next): Operation;
    
    /**
     * @param Signal $signal
     * @return void
     */
    public function streamingFinished(Signal $signal);
    
    public function isLazy(): bool;
}