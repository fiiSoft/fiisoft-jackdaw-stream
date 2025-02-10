<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Mapping\Chunk;

use FiiSoft\Jackdaw\Operation\Mapping\Chunk;
use FiiSoft\Jackdaw\ValueRef\IntValue;

abstract class VolatileSizeChunk extends Chunk
{
    protected IntValue $size;
    
    final protected function __construct(IntValue $size, bool $reindex = false)
    {
        parent::__construct($reindex);
        
        $this->size = $size;
    }
}