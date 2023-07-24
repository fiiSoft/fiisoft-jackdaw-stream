<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Internal;

use FiiSoft\Jackdaw\Internal\ProtectedCloning;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Operation;
use FiiSoft\Jackdaw\Stream;

abstract class BaseOperation extends ProtectedCloning implements Operation
{
    use CommonOperationCode;
    
    final public function assignStream(Stream $stream): void
    {
        if ($this->next !== null) {
            $this->next->assignStream($stream);
        }
    }
    
    public function streamingFinished(Signal $signal): bool
    {
        return $this->next->streamingFinished($signal);
    }
    
    protected function __clone()
    {
        if ($this->next !== null) {
            $this->next = clone $this->next;
            $this->next->setPrev($this);
        }
    }
}