<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Generator;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Producer\Tech\CountableProducer;

final class CombinedArrays extends CountableProducer
{
    private array $keys;
    private array $values;
    
    /**
     * @param array $keys it MUST be standard numerical array indexed from 0
     * @param array $values it MUST be standard numerical array indexed from 0
     */
    public function __construct(array $keys, array $values)
    {
        $this->keys = $keys;
        $this->values = $values;
    }
    
    public function feed(Item $item): \Generator
    {
        for ($i = 0, $j = $this->count(); $i < $j; ++$i) {
            $item->key = $this->keys[$i];
            $item->value = $this->values[$i];
            
            yield;
        }
    }
    
    public function count(): int
    {
        return \min(\count($this->keys), \count($this->values));
    }
    
    public function getLast(): ?Item
    {
        $count = $this->count();
        
        if ($count > 0) {
            $last = $count - 1;
            
            return new Item($this->keys[$last], $this->values[$last]);
        }
        
        return null;
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->keys = $this->values = [];
            
            parent::destroy();
        }
    }
}