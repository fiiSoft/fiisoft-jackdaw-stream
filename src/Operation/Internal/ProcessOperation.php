<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Internal;

use FiiSoft\Jackdaw\Internal\ForkCollaborator;
use FiiSoft\Jackdaw\Operation\Operation;
use FiiSoft\Jackdaw\Stream;

abstract class ProcessOperation extends ForkCollaborator implements Operation
{
    use CommonOperationCode;
    
    public function assignStream(Stream $stream): void
    {
        if ($this->next !== null) {
            $this->next->assignStream($stream);
        }
    }
    
    final protected function __clone()
    {
        if ($this->next !== null) {
            $this->next = clone $this->next;
            $this->next->setPrev($this);
        }
    }
}