<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Internal;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Producer\Tech\CountableProducer;

final class ReverseArrayIterator extends CountableProducer
{
    private array $source;

    private bool $reindex;
    
    public function __construct(array $source, bool $reindex = false)
    {
        $this->source = $source;
        $this->reindex = $reindex;
    }
    
    public function feed(Item $item): \Generator
    {
        if ($this->reindex) {
            $index = 0;
            
            for (
                \end($this->source);
                \key($this->source) !== null;
                \prev($this->source)
            ){
                $item->key = $index++;
                $item->value = \current($this->source);
                
                yield;
            }
        } else {
            for (
                \end($this->source);
                \key($this->source) !== null;
                \prev($this->source)
            ){
                $item->key = \key($this->source);
                $item->value = \current($this->source);
                
                yield;
            }
        }
    }
    
    public function count(): int
    {
        return \count($this->source);
    }
    
    public function getLast(): ?Item
    {
        if (empty($this->source)) {
            return null;
        }
        
        $key = \array_key_first($this->source);
        $value = $this->source[$key];
        
        if ($this->reindex) {
            $key = \count($this->source) - 1;
        }
        
        return new Item($key, $value);
    }
    
    public function destroy(): void
    {
        $this->source = [];
    }
}