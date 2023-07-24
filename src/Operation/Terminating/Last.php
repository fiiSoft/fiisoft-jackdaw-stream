<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Terminating;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\SimpleFinalOperation;
use FiiSoft\Jackdaw\Producer\Producer;

final class Last extends SimpleFinalOperation
{
    private ?Item $item = null;
    private bool $found = false;
    
    public function handle(Signal $signal): void
    {
        if ($this->found === false) {
            $this->found = true;
        }
    }
    
    public function streamingFinished(Signal $signal): bool
    {
        if ($this->found) {
            $this->item = $signal->item->copy();
        }
        
        return $this->next->streamingFinished($signal);
    }
    
    public function hasResult(): bool
    {
        return $this->found;
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
        $last = $producer->getLast();
        if ($last !== null) {
            $this->found = true;
            $signal->item->key = $last->key;
            $signal->item->value = $last->value;
        } else {
            foreach ($producer->feed($signal->item) as $_) {
                //just iterate to the last element
                $this->found = true;
            }
        }
        
        return $this->streamingFinished($signal);
    }
    
    public function acceptSimpleData(array $data, Signal $signal, bool $reindexed): bool
    {
        if (!empty($data)) {
            $this->found = true;
            
            $last = \array_key_last($data);
            $signal->item->key = $last;
            $signal->item->value = $data[$last];
        }
        
        return $this->streamingFinished($signal);
    }
    
    public function acceptCollectedItems(array $items, Signal $signal, bool $reindexed): bool
    {
        if (!empty($items)) {
            $this->found = true;
            
            $last = $items[\array_key_last($items)];
            $signal->item->key = $last->key;
            $signal->item->value = $last->value;
        }
        
        return $this->streamingFinished($signal);
    }
}