<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\State\Tail;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Operation\Tail;
use FiiSoft\Jackdaw\Producer\Internal\CircularBufferIterator;
use FiiSoft\Jackdaw\Producer\Producer;

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
        }
    }
    
    final public function bufferIterator(): Producer
    {
        return new CircularBufferIterator($this->buffer, $this->count(), $this->index);
    }
    
    abstract public function hold(Item $item): void;
    
    /**
     * @return int number of items hold in buffer
     */
    abstract public function count(): int;
    
    final public function destroy(): void
    {
        $this->buffer->setSize(0);
    }
}