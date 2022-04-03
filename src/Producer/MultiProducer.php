<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer;

use FiiSoft\Jackdaw\Internal\Item;

class MultiProducer implements Producer
{
    /** @var Producer[] */
    private array $producers = [];
    
    public function __construct(Producer ...$producers)
    {
        $this->merge($producers);
    }
    
    final public function addProducer(Producer $producer): void
    {
        $this->merge([$producer]);
    }
    
    private function merge(array $producers): void
    {
        foreach ($producers as $producer) {
            if ($producer instanceof self) {
                if (!empty($producer->producers)) {
                    $this->merge($producer->producers);
                }
            } else {
                $this->producers[] = $producer;
            }
        }
    }
    
    public function feed(Item $item): \Generator
    {
        foreach ($this->producers as $producer) {
            $generator = $producer->feed($item);
            while ($generator->valid()) {
                yield;
                $generator->next();
            }
        }
    }
}