<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Internal\ItemBuffer;

use FiiSoft\Jackdaw\Internal\Item;

final class CircularItemBufferFull extends CircularItemBuffer
{
    private Item $current;
    
    public function hold(Item $item): void
    {
        $this->current = $this->buffer[$this->index];
        
        $this->current->key = $item->key;
        $this->current->value = $item->value;
    
        if (++$this->index === $this->size) {
            $this->index = 0;
        }
    }
    
    public function count(): int
    {
        return $this->size;
    }
    
    public function clear(): void
    {
        $this->client->setItemBuffer(self::initial($this->client, $this->size));
    }
}