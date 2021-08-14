<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\State\Tail;

use FiiSoft\Jackdaw\Internal\Item;

final class BufferNotFull extends State
{
    public function hold(Item $item)
    {
        $this->buffer[$this->index] = $item->copy();
        
        if (++$this->index === $this->length) {
            $this->operation->transitTo(new BufferFull($this->operation, $this->buffer));
        }
    }
    
    public function count(): int
    {
        return $this->index;
    }
}