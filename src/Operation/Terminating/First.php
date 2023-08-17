<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Terminating;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\SimpleFinalOperation;
use FiiSoft\Jackdaw\Producer\Producer;

final class First extends SimpleFinalOperation
{
    private ?Item $item = null;
    
    public function handle(Signal $signal): void
    {
        $this->item = $signal->item->copy();
        
        $signal->stop();
    }
    
    public function hasResult(): bool
    {
        return $this->item !== null;
    }
    
    public function getResult(): Item
    {
        return $this->item;
    }
    
    protected function __clone()
    {
        parent::__clone();
        
        $this->item = null;
    }
    
    public function collectDataFromProducer(Producer $producer, Signal $signal, bool $reindexed): bool
    {
        $item = $signal->item;
        
        foreach ($producer->feed($item) as $_) {
            $this->item = $item->copy();
            $signal->stop();
            break;
        }
        
        return $this->streamingFinished($signal);
    }
    
    public function acceptSimpleData(array $data, Signal $signal, bool $reindexed): bool
    {
        $item = $signal->item;
        
        foreach ($data as $item->key => $item->value) {
            $this->item = $item->copy();
            $signal->stop();
            break;
        }
        
        return $this->streamingFinished($signal);
    }
    
    /**
     * @param Item[] $items
     */
    public function acceptCollectedItems(array $items, Signal $signal, bool $reindexed): bool
    {
        foreach ($items as $item) {
            $this->item = $item->copy();
            $signal->stop();
            
            $signal->item->key = $item->key;
            $signal->item->value = $item->value;
            
            break;
        }
        
        return $this->streamingFinished($signal);
    }
}