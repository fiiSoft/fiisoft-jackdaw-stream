<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\State\Tail;

use FiiSoft\Jackdaw\Internal\Item;

final class BufferFull extends State
{
    private Item $current;
    
    public function hold(Item $item): void
    {
        $this->current = $this->buffer[$this->index];
        
        $this->current->key = $item->key;
        $this->current->value = $item->value;
    
        if (++$this->index === $this->length) {
            $this->index = 0;
        }
    }
    
    public function count(): int
    {
        return $this->length;
    }
}