<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\State\SortLimited;

use FiiSoft\Jackdaw\Comparator\Sorting\Sorting;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Operation\SortLimited;

final class BufferNotFull extends HeapBuffer
{
    private int $length;
    private int $count = 0;
    
    public function __construct(SortLimited $operation, Sorting $sorting, int $length)
    {
        parent::__construct($operation, HeapFactory::createHeapForSorting($sorting));
        
        $this->length = $length;
    }
    
    public function hold(Item $item): void
    {
        $this->buffer->insert($item->copy());
    
        if (++$this->count === $this->length) {
            $this->bufferFull();
        }
    }
    
    public function setLength(int $length): void
    {
        $this->length = $length;
    }
    
    private function bufferFull(): void
    {
        $this->operation->transitTo(new BufferFull($this->operation, $this->buffer));
    }
}