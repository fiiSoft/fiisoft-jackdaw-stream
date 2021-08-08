<?php

namespace FiiSoft\Jackdaw\Operation\Internal;

interface Limitable
{
    /**
     * @param int $limit
     * @return void
     */
    public function applyLimit(int $limit);
    
    public function limit(): int;
}