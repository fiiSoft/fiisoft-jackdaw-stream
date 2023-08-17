<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Terminating;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\SimpleFinalOperation;
use FiiSoft\Jackdaw\Producer\Producer;

final class CollectKeys extends SimpleFinalOperation
{
    private array $collected = [];
    
    public function handle(Signal $signal): void
    {
        $this->collected[] = $signal->item->key;
    }
    
    public function hasResult(): bool
    {
        return true;
    }
    
    public function getResult(): Item
    {
        return new Item(0, $this->collected);
    }
    
    public function collectDataFromProducer(Producer $producer, Signal $signal, bool $reindexed): bool
    {
        $item = $signal->item;
        
        foreach ($producer->feed($item) as $_) {
            $this->collected[] = $item->key;
        }
        
        return $this->streamingFinished($signal);
    }
    
    public function acceptSimpleData(array $data, Signal $signal, bool $reindexed): bool
    {
        $this->collected = \array_keys($data);
        
        if (!empty($data)) {
            $last = \array_key_last($data);
            $signal->item->key = $last;
            $signal->item->value = $data[$last];
        }
        
        return $this->streamingFinished($signal);
    }
    
    public function acceptCollectedItems(array $items, Signal $signal, bool $reindexed): bool
    {
        foreach ($items as $item) {
            $this->collected[] = $item->key;
        }
        
        if (isset($item)) {
            $signal->item->key = $item->key;
            $signal->item->value = $item->value;
        }
        
        return $this->streamingFinished($signal);
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->collected = [];
            
            parent::destroy();
        }
    }
}