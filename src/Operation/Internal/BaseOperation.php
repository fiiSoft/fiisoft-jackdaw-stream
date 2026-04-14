<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Internal;

use FiiSoft\Jackdaw\Internal\ProtectedCloning;
use FiiSoft\Jackdaw\Operation\Operation;

abstract class BaseOperation extends ProtectedCloning implements Operation
{
    use CommonOperationCode;
    
    protected function __clone()
    {
        if ($this->next !== null) {
            $this->next = clone $this->next;
            $this->next->setPrev($this);
        }
    }
}