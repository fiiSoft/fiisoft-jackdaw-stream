<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal\Pipe;

use FiiSoft\Jackdaw\Internal\Pipe;
use FiiSoft\Jackdaw\Operation\LastOperation;
use FiiSoft\Jackdaw\Operation\Operation;
use FiiSoft\Jackdaw\Stream;

final class ChainOperationResult
{
    public Pipe $pipe;
    
    public Stream $stream;
    
    /** @var LastOperation|Operation */
    public $operation;
    
    /**
     * @param Operation|LastOperation $operation
     */
    public function __construct(Pipe $pipe, Stream $stream, $operation)
    {
        $this->operation = $operation;
        $this->stream = $stream;
        $this->pipe = $pipe;
    }
    
    public function getLastOperation(): LastOperation
    {
        \assert($this->operation instanceof LastOperation);
        
        return $this->operation;
    }
    
    public function createCanAppendResult(bool $canAppend): CanAppendResult
    {
        return new CanAppendResult($canAppend, $this->pipe, $this->operation);
    }
}