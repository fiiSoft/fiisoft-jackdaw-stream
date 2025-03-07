<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Mapping\Chunk;

use FiiSoft\Jackdaw\Internal\Signal;

final class ManyKeepsKeys extends ConstantSizeChunk
{
    public function handle(Signal $signal): void
    {
        $this->chunked[$signal->item->key] = $signal->item->value;
        
        if (++$this->count === $this->size) {
            $signal->item->key = ++$this->index;
            $signal->item->value = $this->chunked;
            
            $this->count = 0;
            $this->chunked = [];
            
            $this->next->handle($signal);
        }
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            $this->chunked[$key] = $value;
            
            if (++$this->count === $this->size) {
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