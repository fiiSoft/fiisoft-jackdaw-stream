<?php

namespace FiiSoft\Jackdaw\Collector;

interface Collector
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
}