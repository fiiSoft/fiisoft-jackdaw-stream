<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal\Pipe;

use FiiSoft\Jackdaw\Internal\Pipe;
use FiiSoft\Jackdaw\Operation\LastOperation;
use FiiSoft\Jackdaw\Operation\Operation;

final class CanAppendResult
{
    public bool $canAppend;
    
    /** @var LastOperation|Operation|null */
    public $operation;
    
    public ?Pipe $pipe = null;
    
    /**
     * @param LastOperation|Operation|null $operation
     */
    public function __construct(bool $canAppend, ?Pipe $pipe = null, $operation = null)
    {
        $this->canAppend = $canAppend;
        $this->operation = $operation;
        $this->pipe = $pipe;
    }
}