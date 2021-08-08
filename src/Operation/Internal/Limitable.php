<?php

namespace FiiSoft\Jackdaw\Operation\Internal;

interface Limitable
{
    public function applyLimit(int $limit): void;
    
    public function limit(): int;
}