<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Filtering\Uptrends;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Filtering\Uptrends;

final class UptrendsKeepKeys extends Uptrends
{
    public function handle(Signal $signal): void
    {
        $item = $signal->item;
        
        if ($this->previous === null) {
            $this->previous = clone $item;
        } elseif ($this->comparator->compare($this->previous, $item) < 0) {
            $this->trend[$this->previous->key] = $this->previous->value;
            
            $this->previous->key = $item->key;
            $this->previous->value = $item->value;
        } else {
            if (!empty($this->trend)) {
                $this->trend[$this->previous->key] = $this->previous->value;
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
            } elseif ($this->comparator->compare($this->previous, $item) < 0) {
                $this->trend[$this->previous->key] = $this->previous->value;
                
                $this->previous->key = $item->key;
                $this->previous->value = $item->value;
            } else {
                if (!empty($this->trend)) {
                    $this->trend[$this->previous->key] = $this->previous->value;
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
            $this->trend[$this->previous->key] = $this->previous->value;
            
            yield ++$this->index => $this->trend;
            
            $this->trend = [];
        }
    }
}