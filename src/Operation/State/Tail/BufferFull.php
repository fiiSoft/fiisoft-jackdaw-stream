<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\State\Tail;

use FiiSoft\Jackdaw\Internal\Item;

final class BufferFull extends State
{
    public function hold(Item $item)
    {
        $item->copyTo($this->buffer[$this->index]);
    
        if (++$this->index === $this->length) {
            $this->index = 0;
        }
    }
    
    public function count(): int
    {
        return $this->length;
    }
}