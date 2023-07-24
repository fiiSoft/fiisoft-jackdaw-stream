<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Operation\Internal\DataCollector;
use FiiSoft\Jackdaw\Producer\Internal\ReverseArrayIterator;
use FiiSoft\Jackdaw\Producer\Internal\ReverseItemsIterator;
use FiiSoft\Jackdaw\Producer\Producer;

final class Reverse extends BaseOperation implements DataCollector
{
    /** @var Item[] */
    private array $items = [];
    
    public function handle(Signal $signal): void
    {
        $this->items[] = $signal->item->copy();
    }
    
    public function streamingFinished(Signal $signal): bool
    {
        if (empty($this->items)) {
            return parent::streamingFinished($signal);
        }
        
        $producer = new ReverseItemsIterator($this->items);
        
        if ($this->next instanceof DataCollector) {
            $signal->continueFrom($this->next);
            
            return $this->next->collectDataFromProducer($producer, $signal, false);
        }
        
        $signal->restartWith($producer, $this->next);
        
        return true;
    }
    
    public function collectDataFromProducer(Producer $producer, Signal $signal, bool $reindexed): bool
    {
        $item = $signal->item;
        
        foreach ($producer->feed($item) as $_) {
            $this->items[] = $item->copy();
        }
        
        return $this->streamingFinished($signal);
    }
    
    public function acceptSimpleData(array $data, Signal $signal, bool $reindexed): bool
    {
        if (empty($data)) {
            return $this->streamingFinished($signal);
        }
        
        $producer = new ReverseArrayIterator($data);
        
        if ($this->next instanceof DataCollector) {
            $signal->continueFrom($this->next);
            
            return $this->next->collectDataFromProducer($producer, $signal, $reindexed);
        }
        
        $signal->restartWith($producer, $this->next);
        
        return true;
    }
    
    /**
     * @param bool $reindexed
     * @param Item[] $items
     */
    public function acceptCollectedItems(array $items, Signal $signal, bool $reindexed): bool
    {
        if (empty($items)) {
            return $this->streamingFinished($signal);
        }
        
        $producer = new ReverseItemsIterator($items);
        
        if ($this->next instanceof DataCollector) {
            $signal->continueFrom($this->next);
            
            return $this->next->collectDataFromProducer($producer, $signal, $reindexed);
        }
        
        $signal->restartWith($producer, $this->next);
        
        return true;
    }
}