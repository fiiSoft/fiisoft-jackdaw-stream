<?php

namespace FiiSoft\Jackdaw\Internal;

use FiiSoft\Jackdaw\Consumer\Consumer;

interface Result extends StreamPipe, ResultCaster
{
    public function run(): void;
    
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
    
    /**
     * @return array with two values: first is key, second is value, both indexed numerically
     */
    public function tuple(): array;
    
    /**
     * @param Consumer|callable $consumer
     * @return void
     */
    public function call($consumer): void;
}