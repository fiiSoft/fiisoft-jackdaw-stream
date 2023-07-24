<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Producer\Tech\BaseProducer;
use FiiSoft\Jackdaw\Producer\Tech\MultiSourcedProducer;

final class MultiProducer extends BaseProducer implements MultiSourcedProducer
{
    /** @var Producer[] */
    protected array $producers = [];
    
    public function __construct(Producer ...$producers)
    {
        $this->merge($producers);
    }
    
    public function addProducer(Producer $producer): void
    {
        $this->merge([$producer]);
    }
    
    /**
     * @param Producer[] $producers
     */
    private function merge(array $producers): void
    {
        foreach ($producers as $producer) {
            if ($producer instanceof MultiSourcedProducer) {
                $this->merge($producer->getProducers());
            } elseif ($producer instanceof QueueProducer || !$producer->isEmpty()) {
                $this->producers[] = $producer;
            }
        }
    }
    
    public function feed(Item $item): \Generator
    {
        foreach ($this->producers as $producer) {
            yield from $producer->feed($item);
        }
    }
    
    public function isEmpty(): bool
    {
        foreach ($this->producers as $producer) {
            if (!$producer->isEmpty()) {
                return false;
            }
        }
        
        return true;
    }
    
    public function isCountable(): bool
    {
        foreach ($this->producers as $producer) {
            if (!$producer->isCountable()) {
                return false;
            }
        }
        
        return true;
    }
    
    public function count(): int
    {
        if ($this->isCountable()) {
            $count = 0;
            foreach ($this->producers as $producer) {
                $count += $producer->count();
            }
            
            return $count;
        }
        
        throw new \BadMethodCallException('MultiProducer cannot count how many elements can produce!');
    }
    
    public function getLast(): ?Item
    {
        if (empty($this->producers)) {
            return null;
        }
        
        $last = \array_key_last($this->producers);
        
        return $this->producers[$last]->getLast();
    }
    
    public function getProducers(): array
    {
        return $this->producers;
    }
}