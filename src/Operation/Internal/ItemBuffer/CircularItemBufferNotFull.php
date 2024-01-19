<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Internal\ItemBuffer;

use FiiSoft\Jackdaw\Internal\Item;

final class CircularItemBufferNotFull extends CircularItemBuffer
{
    public function hold(Item $item): void
    {
        $this->buffer[$this->index] = $item->copy();
        
        if (++$this->index === $this->size) {
            $this->client->setItemBuffer(self::full($this->client, $this->buffer));
        }
    }
    
    public function count(): int
    {
        return $this->index;
    }
    
    public function clear(): void
    {
        $this->client->setItemBuffer(self::initial($this->client, $this->size));
    }
}