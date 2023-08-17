<?php

namespace FiiSoft\Jackdaw\Mapper;

interface Mapper
{
    /**
     * @param mixed $value
     * @param string|int $key
     * @return mixed
     */
    public function map($value, $key);
    
    /**
     * @return bool true when other mapper has been merged
     */
    public function mergeWith(Mapper $other): bool;
    
    public function isStateless(): bool;
}