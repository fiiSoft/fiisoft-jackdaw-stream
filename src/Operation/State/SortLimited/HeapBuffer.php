<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\State\SortLimited;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Operation\SortLimited;

abstract class HeapBuffer extends State
{
    /** @var \SplHeap<Item> */
    protected \SplHeap $buffer;
    
    public function __construct(SortLimited $operation, \SplHeap $buffer)
    {
        parent::__construct($operation);
        
        $this->buffer = $buffer;
    }
    
    final public function isEmpty(): bool
    {
        return $this->buffer->isEmpty();
    }
    
    /**
     * @return Item[]
     */
    final public function getCollectedItems(): array
    {
        $data = [];
        $this->buffer->rewind();
        
        while (!$this->buffer->isEmpty()) {
            $data[] = $this->buffer->extract();
        }
        
        return $data;
    }
    
    public function destroy(): void
    {
        $this->buffer->rewind();
        while (!$this->buffer->isEmpty()) {
            $this->buffer->extract();
        }
    }
}