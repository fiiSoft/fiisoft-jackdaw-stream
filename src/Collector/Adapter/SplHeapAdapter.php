<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Collector\Adapter;

use FiiSoft\Jackdaw\Collector\Collector;

final class SplHeapAdapter implements Collector
{
    private \SplHeap $heap;
    
    public function __construct(\SplHeap $heap)
    {
        $this->heap = $heap;
    }
    
    /**
     * @inheritDoc
     */
    public function set($key, $value): void
    {
        throw new \LogicException('You cannot keep keys and values in '.\get_class($this->heap));
    }
    
    /**
     * @inheritDoc
     */
    public function add($value): void
    {
        $this->heap->insert($value);
    }
    
    public function canPreserveKeys(): bool
    {
        return false;
    }
    
    public function allowKeys(?bool $allowKeys): void
    {
        //noop
    }
}