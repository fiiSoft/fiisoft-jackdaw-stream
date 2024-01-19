<?php

namespace FiiSoft\Jackdaw\Operation\Internal;

interface Limitable
{
    public function createWithLimit(int $limit): self;
    
    /**
     * @return bool returns true when limit is applied, otherwise returns false
     */
    public function applyLimit(int $limit): bool;
    
    public function limit(): int;
}