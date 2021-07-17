<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer;

use FiiSoft\Jackdaw\Internal\Item;

final class MultiProducer implements Producer
{
    /** @var Producer[] */
    private $producers = [];
    
    /**
     * @param Producer ...$producers
     */
    public function __construct(...$producers)
    {
        foreach ($producers as $producer) {
            $this->addProducer($producer);
        }
    }
    
    public function addProducer(Producer $producer)
    {
        $this->producers[] = $producer;
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