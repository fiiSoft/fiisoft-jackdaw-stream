<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\State\Tail;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Operation\Tail;

abstract class State
{
    protected Tail $operation;
    protected \SplFixedArray $buffer;
    
    protected int $length;
    protected int $index = 0;
    
    final public function __construct(Tail $operation, \SplFixedArray $buffer)
    {
        $this->operation = $operation;
        $this->buffer = $buffer;
        $this->length = $buffer->getSize();
    }
    
    final public function setLength(int $length): void
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
    
    abstract public function hold(Item $item): void;
    
    /**
     * @return int number of items hold in buffer
     */
    abstract public function count(): int;
}