<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\State\SortLimited;

use FiiSoft\Jackdaw\Internal\Item;

final class BufferFull extends HeapBuffer
{
    private Item $top;
    
    public function hold(Item $item): void
    {
        $this->top = $this->buffer->top();
        
        if ($this->buffer->compare($item, $this->top) < 0) {
            $this->buffer->extract();
            
            $this->top->key = $item->key;
            $this->top->value = $item->value;
            
            $this->buffer->insert($this->top);
        }
    }
    
    public function setLength(int $length): void
    {
        throw new \LogicException('Change of size of full buffer is prohibited');
    }
}