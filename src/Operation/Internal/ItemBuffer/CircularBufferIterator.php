<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Internal\ItemBuffer;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Producer\Tech\BaseProducer;

final class CircularBufferIterator extends BaseProducer
{
    /** @var \SplFixedArray<Item>|\ArrayAccess<int, Item>|array<int, Item> */
    private $buffer;
    
    private int $count; //number of elements in buffer
    private int $index; //index of first element
    
    /**
     * @param \SplFixedArray<Item>|\ArrayAccess<int, Item>|array<int, Item> $buffer
     */
    public function __construct($buffer, int $count, int $index)
    {
        if (\is_array($buffer) || $buffer instanceof \ArrayAccess) {
            $this->buffer = $buffer;
        } else {
            throw InvalidParamException::describe('buffer', $buffer);
        }
    
        if ($count >= 0) {
            $this->count = $count;
        } else {
            throw InvalidParamException::describe('count', $count);
        }
        
        if ($index >= 0 && $index <= $count) {
            $this->index = $index;
        } else {
            throw InvalidParamException::describe('index', $index);
        }
    }
    
    public function getIterator(): \Generator
    {
        for ($i = 0, $j = $this->count; $i < $j; ++$i) {
            if ($this->index === $this->count) {
                $this->index = 0;
            }
    
            $x = $this->buffer[$this->index++];
            
            yield $x->key => $x->value;
        }
    
        $this->destroy();
    }
    
    public function destroy(): void
    {
        $this->count = 0;
        $this->buffer = [];
    }
}