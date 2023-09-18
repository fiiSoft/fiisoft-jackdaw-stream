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
    
    /**
     * @return Mapper a new instance that "knows" it's for mapping keys instead of values
     */
    public function makeKeyMapper(): Mapper;
}