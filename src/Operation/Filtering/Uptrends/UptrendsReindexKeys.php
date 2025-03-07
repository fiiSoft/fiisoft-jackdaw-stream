<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Filtering\Uptrends;

use FiiSoft\Jackdaw\Comparator\ItemComparator\ItemComparatorFactory;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Filtering\Uptrends;

final class UptrendsReindexKeys extends Uptrends
{
    public function handle(Signal $signal): void
    {
        $item = $signal->item;
        
        if ($this->previous === null) {
            $this->previous = clone $item;
            $this->comparator = ItemComparatorFactory::getForComparison($this->comparison, $this->downtrend);
        } elseif ($this->comparator->compare($this->previous, $item) < 0) {
            $this->trend[] = $this->previous->value;
            
            $this->previous->key = $item->key;
            $this->previous->value = $item->value;
        } else {
            if (!empty($this->trend)) {
                $this->trend[] = $this->previous->value;
            }
            
            $this->previous->key = $item->key;
            $this->previous->value = $item->value;
            
            if (!empty($this->trend)) {
                $item->key = ++$this->index;
                $item->value = $this->trend;
                
                $this->next->handle($signal);
                
                $this->trend = [];
            }
        }
    }
    
    public function buildStream(iterable $stream): iterable
    {
        $item = new Item();
        
        foreach ($stream as $item->key => $item->value) {
            if ($this->previous === null) {
                $this->previous = clone $item;
                $this->comparator = ItemComparatorFactory::getForComparison($this->comparison, $this->downtrend);
            } elseif ($this->comparator->compare($this->previous, $item) < 0) {
                $this->trend[] = $this->previous->value;
                
                $this->previous->key = $item->key;
                $this->previous->value = $item->value;
            } else {
                if (!empty($this->trend)) {
                    $this->trend[] = $this->previous->value;
                }
                
                $this->previous->key = $item->key;
                $this->previous->value = $item->value;
                
                if (!empty($this->trend)) {
                    yield ++$this->index => $this->trend;
                    
                    $this->trend = [];
                }
            }
        }
        
        if (!empty($this->trend)) {
            $this->trend[] = $this->previous->value;
            
            yield ++$this->index => $this->trend;
            
            $this->trend = [];
        }
    }
}