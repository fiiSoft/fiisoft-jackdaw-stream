<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Generator;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Producer\Producer;
use FiiSoft\Jackdaw\Producer\ProducerReady;
use FiiSoft\Jackdaw\Producer\Producers;
use FiiSoft\Jackdaw\Producer\Tech\BaseProducer;

final class CombinedGeneral extends BaseProducer
{
    private Producer $keys, $values;
    
    /**
     * @param ProducerReady|resource|callable|iterable $keys
     * @param ProducerReady|resource|callable|iterable $values
     */
    public function __construct($keys, $values)
    {
        $this->keys = Producers::getAdapter($keys);
        $this->values = Producers::getAdapter($values);
    }
    
    public function feed(Item $item): \Generator
    {
        $key = new Item();
        $keyFetcher = $this->keys->feed($key);
        
        $value = new Item();
        $valueFetcher = $this->values->feed($value);

        while ($keyFetcher->valid() && $valueFetcher->valid()) {
            $item->key = $key->value;
            $item->value = $value->value;

            yield;

            $keyFetcher->next();
            $valueFetcher->next();
        }
    }
    
    public function isEmpty(): bool
    {
        return $this->keys->isEmpty() || $this->values->isEmpty();
    }
    
    public function isCountable(): bool
    {
        return $this->keys->isCountable() && $this->values->isCountable();
    }
    
    public function count(): int
    {
        if ($this->isCountable()) {
            return \min($this->keys->count(), $this->values->count());
        }
        
        throw new \BadMethodCallException('CombinedGeneral producer cannot count how many elements can produce!');
    }
    
    public function getLast(): ?Item
    {
        return null;
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->keys->destroy();
            $this->values->destroy();
            
            parent::destroy();
        }
    }
}