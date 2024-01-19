<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Adapter;

use FiiSoft\Jackdaw\Producer\Tech\BaseProducer;

final class CallableAdapter extends BaseProducer
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
    
    public function getIterator(): \Generator
    {
        yield from ($this->factory)();
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->factory = static fn(): array => [];
            
            parent::destroy();
        }
    }
}