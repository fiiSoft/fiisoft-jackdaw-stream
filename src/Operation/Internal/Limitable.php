<?php

namespace FiiSoft\Jackdaw\Operation\Internal;

interface Limitable
{
    /**
     * @return bool returns true when limit is applied, otherwise returns false
     */
    public function applyLimit(int $limit): bool;
    
    public function limit(): int;
}