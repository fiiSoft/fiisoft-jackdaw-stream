<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Internal\ItemBuffer;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Producer\Producer;
use FiiSoft\Jackdaw\Producer\Producers;

final class SingleItemBuffer implements ItemBuffer
{
    private ?Item $item = null;
    
    public function hold(Item $item): void
    {
        if ($this->item === null) {
            $this->item = clone $item;
        } else {
            $this->item->key = $item->key;
            $this->item->value = $item->value;
        }
    }
    
    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return $this->item === null ? 0 : 1;
    }
    
    /**
     * @inheritDoc
     */
    public function clear(): void
    {
        $this->item = null;
    }
    
    public function getLength(): int
    {
        return 1;
    }
    
    /**
     * @inheritDoc
     */
    public function fetchData(bool $reindex = false, int $skip = 0): array
    {
        return $skip > 0 ? [] : [$reindex ? 0 : $this->item->key => $this->item->value];
    }
    
    public function createProducer(): Producer
    {
        return Producers::getAdapter($this->fetchData());
    }
    
    public function destroy(): void
    {
        $this->clear();
    }
}