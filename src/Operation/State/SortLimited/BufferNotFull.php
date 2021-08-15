<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\State\SortLimited;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Operation\SortLimited;
use SplHeap;

final class BufferNotFull extends State
{
    /** @var int */
    private $length;
    
    /** @var int */
    private $count = 0;
    
    public function __construct(SortLimited $operation, SplHeap $buffer, int $length)
    {
        parent::__construct($operation, $buffer);
        
        $this->length = $length;
    }
    
    public function hold(Item $item)
    {
        $this->buffer->insert($item->copy());
    
        if (++$this->count === $this->length) {
            $this->bufferFull();
        }
    }
    
    public function setLength(int $length)
    {
        if ($length !== $this->length) {
            if ($length < $this->count) {
                do {
                    $this->buffer->extract();
                } while (--$this->count > $length);
            }
            
            $this->length = $length;
    
            if ($this->count === $this->length) {
                $this->bufferFull();
            }
        }
    }
    
    private function bufferFull()
    {
        $this->operation->transitTo(new BufferFull($this->operation, $this->buffer));
    }
}