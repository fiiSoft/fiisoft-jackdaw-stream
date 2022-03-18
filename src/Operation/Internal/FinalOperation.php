<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Internal;

use FiiSoft\Jackdaw\Internal\Result;
use FiiSoft\Jackdaw\Internal\ResultProvider;
use FiiSoft\Jackdaw\Stream;

abstract class FinalOperation extends BaseOperation
{
    private Result $result;
    
    /**
     * @param Stream $stream
     * @param ResultProvider $resultProvider
     * @param callable|mixed|null $orElse
     */
    public function __construct(Stream $stream, ResultProvider $resultProvider, $orElse = null)
    {
        $this->result = new Result($stream, $resultProvider, $orElse);
    }
    
    final public function isLazy(): bool
    {
        return true;
    }
    
    final public function result(): Result
    {
        return $this->result;
    }
}