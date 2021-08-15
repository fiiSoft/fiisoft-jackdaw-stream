<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\State\SortLimited;

use FiiSoft\Jackdaw\Internal\Item;

final class BufferFull extends State
{
    public function hold(Item $item)
    {
        if ($this->buffer->compare($item, $this->buffer->top()) < 0) {
            $this->buffer->extract();
            $this->buffer->insert($item->copy());
        }
    }
    
    public function setLength(int $length)
    {
        throw new \LogicException('Change of size of full buffer is prohibited');
    }
}