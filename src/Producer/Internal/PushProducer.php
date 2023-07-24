<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Internal;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Producer\QueueProducer;
use FiiSoft\Jackdaw\Producer\Tech\MultiSourcedProducer;
use FiiSoft\Jackdaw\Producer\Tech\NonCountableProducer;
use FiiSoft\Jackdaw\Producer\Producer;

final class PushProducer extends NonCountableProducer implements MultiSourcedProducer
{
    /** @var Producer[] */
    private array $producers = [];
    
    private ?Item $next = null;
    
    private bool $isLoop;
    
    public function __construct(bool $isLoop, Producer ...$producers)
    {
        $this->isLoop = $isLoop;
        
        $this->merge($producers);
    }
    
    public function feed(Item $item): \Generator
    {
        $i = 0;
        $j = \count($this->producers);
        
        if ($this->isLoop) {
            for (; $i < $j; ++$i) {
                $generator = $this->producers[$i]->feed($item);
                if ($generator->valid()) {
                    yield;
                    goto LOOP;
                }
            }
        } elseif ($j > 0) {
            $i = -1;
        }
        
        $generator = (static function () { yield; })();
        
        LOOP:
        do {
            $this->next = yield;
            
            while ($this->next !== null) {
                $item->key = $this->next->key;
                $item->value = $this->next->value;
                yield;
                
                $this->next = yield;
            }
            
            $generator->next();
        }
        while ($generator->valid());
        
        for (++$i; $i < $j; ++$i) {
            $generator = $this->producers[$i]->feed($item);
            if ($generator->valid()) {
                goto LOOP;
            }
        }
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
    
    public function getProducers(): array
    {
        return $this->producers;
    }
}