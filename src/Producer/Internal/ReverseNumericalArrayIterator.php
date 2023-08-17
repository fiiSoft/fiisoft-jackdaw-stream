<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Internal;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Producer\Tech\CountableProducer;

final class ReverseNumericalArrayIterator extends CountableProducer
{
    private array $data;
    
    private bool $reindex;
    
    public function __construct(array $data, bool $reindex = false)
    {
        $this->data = $data;
        $this->reindex = $reindex;
    }
    
    public function feed(Item $item): \Generator
    {
        if ($this->reindex) {
            for ($index = 0, $i = $this->count() - 1; $i >= 0; --$i) {
                $item->key = $index++;
                $item->value = $this->data[$i];
                yield;
            }
        } else {
            for ($i = $this->count() - 1; $i >= 0; --$i) {
                $item->key = $i;
                $item->value = $this->data[$i];
                yield;
            }
        }
    }
    
    public function count(): int
    {
        return \count($this->data);
    }
    
    public function getLast(): ?Item
    {
        if (empty($this->data)) {
            return null;
        }
        
        $key = $this->reindex ? $this->count() - 1 : 0;
        
        return new Item($key, $this->data[0]);
    }
    
    public function destroy(): void
    {
        $this->data = [];
    }
}