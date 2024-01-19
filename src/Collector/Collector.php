<?php

namespace FiiSoft\Jackdaw\Collector;

use FiiSoft\Jackdaw\Operation\Sending\Dispatcher\HandlerReady;

interface Collector extends HandlerReady
{
    /**
     * @param string|int $key
     * @param mixed $value
     * @return void
     */
    public function set($key, $value): void;
    
    /**
     * @param mixed $value
     * @return void
     */
    public function add($value): void;
    
    public function canPreserveKeys(): bool;
    
    public function allowKeys(?bool $allowKeys): void;
}