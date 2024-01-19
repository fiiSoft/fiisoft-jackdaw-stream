<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer;

use FiiSoft\Jackdaw\Producer\Tech\BaseProducer;
use FiiSoft\Jackdaw\Producer\Tech\MultiSourcedProducer;

final class MultiProducer extends BaseProducer implements MultiSourcedProducer
{
    /** @var Producer[] */
    private array $producers = [];
    
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
            } else {
                $this->producers[] = $producer;
            }
        }
    }
    
    public function getIterator(): \Generator
    {
        foreach ($this->producers as $producer) {
            yield from $producer;
        }
    }
    
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