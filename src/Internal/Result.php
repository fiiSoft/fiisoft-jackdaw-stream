<?php

namespace FiiSoft\Jackdaw\Internal;

use FiiSoft\Jackdaw\Consumer\Consumer;

interface Result extends StreamPipe
{
    /**
     * @return void
     */
    public function run();
    
    public function found(): bool;
    
    public function notFound(): bool;
    
    /**
     * @return mixed|null
     */
    public function get();
    
    /**
     * @return int|string
     */
    public function key();
    
    public function toString(): string;
    
    public function toJson(): string;
    
    public function toJsonAssoc(): string;
    
    public function toArray(): array;
    
    public function toArrayAssoc(): array;
    
    public function tuple(): array;
    
    /**
     * @param Consumer|callable $consumer
     * @return void
     */
    public function call($consumer);
}