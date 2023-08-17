<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Adapter;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Producer\Tech\NonCountableProducer;

final class CallableAdapter extends NonCountableProducer
{
    /** @var callable */
    private $factory;
    
    /**
     * @param callable $factory it MUST produce anything iterable with valid keys and values
     */
    public function __construct(callable $factory)
    {
        $this->factory = $factory;
    }
    
    public function feed(Item $item): \Generator
    {
        $factory = $this->factory;
        
        foreach ($factory() as $item->key => $item->value) {
            yield;
        }
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->factory = static fn(): array => [];
            
            parent::destroy();
        }
    }
}