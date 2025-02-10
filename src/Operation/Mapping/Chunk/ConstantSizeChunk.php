<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Mapping\Chunk;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Operation\Mapping\Chunk;

abstract class ConstantSizeChunk extends Chunk
{
    protected int $size;
    
    final protected function __construct(int $size, bool $reindex = false)
    {
        parent::__construct($reindex);
        
        if ($size < 1) {
            throw InvalidParamException::describe('size', $size);
        }
        
        $this->size = $size;
    }
}