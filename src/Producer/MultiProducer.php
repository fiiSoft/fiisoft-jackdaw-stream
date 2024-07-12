<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer;

use FiiSoft\Jackdaw\Producer\Internal\EmptyProducer;
use FiiSoft\Jackdaw\Producer\Tech\BaseProducer;

final class MultiProducer extends BaseProducer
{
    /** @var Producer[] */
    private array $producers = [];
    
    private bool $onceTimeIterator;
    
    public static function repeatable(Producer ...$producers): MultiProducer
    {
        return new self(false, ...$producers);
    }
    
    public static function oneTime(Producer ...$producers): MultiProducer
    {
        return new self(true, ...$producers);
    }
    
    private function __construct(bool $onceTimeIterator, Producer ...$producers)
    {
        $this->onceTimeIterator = $onceTimeIterator;
        
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
            if ($producer instanceof self) {
                $this->merge($producer->getProducers());
            } elseif (! $producer instanceof EmptyProducer) {
                $this->producers[] = $producer;
            }
        }
    }
    
    public function getIterator(): \Generator
    {
        foreach ($this->producers as $producer) {
            yield from $producer;
        }
        
        if ($this->onceTimeIterator) {
            $this->producers = [];
        }
    }
    
    public function isOneTime(): bool
    {
        return $this->onceTimeIterator;
    }
    
    public function prepare(): Producer
    {
        return \count($this->producers) === 1 ? $this->producers[0] : $this;
    }
    
    /**
     * @return Producer[]
     */
    public function getProducers(): array
    {
        return $this->producers;
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            foreach ($this->producers as $producer) {
                $producer->destroy();
            }
            
            $this->producers = [];
            
            parent::destroy();
        }
    }
}