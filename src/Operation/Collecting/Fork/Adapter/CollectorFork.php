<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Collecting\Fork\Adapter;

use FiiSoft\Jackdaw\Collector\IterableCollector;
use FiiSoft\Jackdaw\Operation\Collecting\Fork\ForkHandler;

abstract class CollectorFork implements ForkHandler
{
    protected IterableCollector $collector;
    
    final public static function createAdapter(IterableCollector $collector): CollectorFork
    {
        return $collector->canPreserveKeys()
            ? new CollectorWithKeys($collector)
            : new CollectorWithoutKeys($collector);
    }
    
    final protected function __construct(IterableCollector $collector)
    {
        $this->collector = $collector;
    }
    
    final public function create(): ForkHandler
    {
        return new static(clone $this->collector);
    }
    
    final public function isEmpty(): bool
    {
        return $this->collector->count() === 0;
    }
    
    /**
     * @inheritDoc
     */
    final public function result()
    {
        return $this->collector->toArray();
    }
    
    final public function destroy(): void
    {
        $this->collector->clear();
    }
}