<?php

namespace FiiSoft\Jackdaw\Operation\State\Tail;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Operation\Tail;

abstract class State
{
    /** @var Tail */
    protected $operation;
    
    /** @var \SplFixedArray */
    protected $buffer;
    
    /** @var int */
    protected $length;
    
    /** @var int */
    protected $index = 0;
    
    final public function __construct(Tail $operation, \SplFixedArray $buffer)
    {
        $this->operation = $operation;
        $this->buffer = $buffer;
        $this->length = $buffer->getSize();
    }
    
    /**
     * @param int $length
     * @return void
     */
    final public function setLength(int $length)
    {
        if ($length !== $this->length) {
            $this->length = $length;
            $this->buffer->setSize($this->length);
            
            if ($this->index >= $this->length) {
                $this->index = 0;
            }
        }
    }
    
    /**
     * @return Item[]
     */
    final public function fetchItems(): array
    {
        $items = [];
        $count = $this->count();
    
        for ($i = 0; $i < $count; ++$i) {
            if ($this->index === $count) {
                $this->index = 0;
            }
        
            $items[] = $this->buffer[$this->index++];
        }
    
        return $items;
    }
    
    /**
     * @param Item $item
     * @return void
     */
    abstract public function hold(Item $item);
    
    /**
     * @return int number of items hold in buffer
     */
    abstract public function count(): int;
}