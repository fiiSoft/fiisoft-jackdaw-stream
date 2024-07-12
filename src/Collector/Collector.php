<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Collector;

use FiiSoft\Jackdaw\Operation\Sending\Dispatcher\HandlerReady;

interface Collector extends HandlerReady
{
    /**
     * @param string|int $key
     * @param mixed $value
     */
    public function set($key, $value): void;
    
    /**
     * @param mixed $value
     */
    public function add($value): void;
    
    public function canPreserveKeys(): bool;
    
    public function allowKeys(?bool $allowKeys): void;
}