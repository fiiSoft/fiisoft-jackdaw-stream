<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Internal;

use FiiSoft\Jackdaw\Operation\Operation;

interface Limitable extends Operation
{
    public function createWithLimit(int $limit): self;
    
    /**
     * @return bool returns true when limit is applied, otherwise returns false
     */
    public function applyLimit(int $limit): bool;
    
    public function limit(): int;
}