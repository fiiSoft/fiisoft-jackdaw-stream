<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Terminating;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\SimpleFinalOperation;
use FiiSoft\Jackdaw\Producer\Producer;

final class Count extends SimpleFinalOperation
{
    private int $count = 0;
    
    public function handle(Signal $signal): void
    {
        ++$this->count;
    }
    
    public function hasResult(): bool
    {
        return true;
    }
    
    public function getResult(): Item
    {
        return new Item(0, $this->count);
    }
    
    public function collectDataFromProducer(Producer $producer, Signal $signal, bool $reindexed): bool
    {
        if ($producer->isCountable()) {
            $this->count = $producer->count();
        } else {
            foreach ($producer->feed($signal->item) as $_) {
                ++$this->count;
            }
        }
        
        return $this->streamingFinished($signal);
    }
    
    public function acceptSimpleData(array $data, Signal $signal, bool $reindexed): bool
    {
        $this->count = \count($data);
        
        if ($this->count > 0) {
            $last = \array_key_last($data);
            $signal->item->key = $last;
            $signal->item->value = $data[$last];
        }
        
        return $this->streamingFinished($signal);
    }
    
    public function acceptCollectedItems(array $items, Signal $signal, bool $reindexed): bool
    {
        $this->count = \count($items);
        
        if ($this->count > 0) {
            $last = $items[\array_key_last($items)];
            $signal->item->key = $last->key;
            $signal->item->value = $last->value;
        }
        
        return $this->streamingFinished($signal);
    }
}