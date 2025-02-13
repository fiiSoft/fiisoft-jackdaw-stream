<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Internal\ItemBuffer;

use FiiSoft\Jackdaw\Internal\Item;

final class CircularItemBufferNotFull extends CircularItemBuffer
{
    public function hold(Item $item): void
    {
        $this->buffer[$this->index] = clone $item;
        
        if (++$this->index === $this->size) {
            $this->client->setItemBuffer(self::full($this->client, $this->buffer));
            $this->client = null;
        }
    }
    
    public function count(): int
    {
        return $this->index;
    }
}