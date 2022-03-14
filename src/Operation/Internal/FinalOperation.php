<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Internal;

use FiiSoft\Jackdaw\Internal\LazyResult;
use FiiSoft\Jackdaw\Internal\ResultProvider;
use FiiSoft\Jackdaw\Stream;

abstract class FinalOperation extends BaseOperation
{
    private LazyResult $result;
    
    /**
     * @param Stream $stream
     * @param ResultProvider $resultProvider
     * @param callable|mixed|null $orElse
     */
    public function __construct(Stream $stream, ResultProvider $resultProvider, $orElse = null)
    {
        $this->result = new LazyResult($stream, $resultProvider, $orElse);
    }
    
    final public function isLazy(): bool
    {
        return true;
    }
    
    final public function result(): LazyResult
    {
        return $this->result;
    }
}