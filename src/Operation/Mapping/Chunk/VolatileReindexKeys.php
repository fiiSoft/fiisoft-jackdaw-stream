<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Mapping\Chunk;

use FiiSoft\Jackdaw\Internal\Signal;

final class VolatileReindexKeys extends VolatileSizeChunk
{
    public function handle(Signal $signal): void
    {
        $this->chunked[] = $signal->item->value;
        
        if (++$this->count >= $this->size->int()) {
            $signal->item->key = ++$this->index;
            $signal->item->value = $this->chunked;
            
            $this->count = 0;
            $this->chunked = [];
            
            $this->next->handle($signal);
        }
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $value) {
            $this->chunked[] = $value;
            
            if (++$this->count >= $this->size->int()) {
                yield ++$this->index => $this->chunked;
                
                $this->count = 0;
                $this->chunked = [];
            }
        }
        
        if (!empty($this->chunked)) {
            yield ++$this->index => $this->chunked;
            
            $this->count = 0;
            $this->chunked = [];
        }
    }
}