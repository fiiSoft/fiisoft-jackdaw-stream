<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Collecting;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Operation\Internal\ItemBuffer\CircularItemBuffer;
use FiiSoft\Jackdaw\Operation\Internal\ItemBuffer\ItemBuffer;
use FiiSoft\Jackdaw\Operation\Internal\ItemBuffer\ItemBufferClient;

final class Tail extends BaseOperation implements ItemBufferClient
{
    private ItemBuffer $buffer;
    private int $length;
    
    public function __construct(int $length)
    {
        if ($length < 1) {
            throw InvalidParamException::describe('length', $length);
        }
        
        $this->length = $length;
    }
    
    public function prepare(): void
    {
        parent::prepare();
        
        $this->prepareBuffer();
    }
    
    public function mergeWith(Tail $other): void
    {
        $this->length = \min($this->length, $other->length);
    }
    
    private function prepareBuffer(): void
    {
        $this->buffer = CircularItemBuffer::initial($this, $this->length);
    }
    
    public function handle(Signal $signal): void
    {
        $this->buffer->hold($signal->item);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        $item = new Item();
        
        foreach ($stream as $item->key => $item->value) {
            $this->buffer->hold($item);
        }
        
        if ($this->buffer->count() === 0) {
            return [];
        }
        
        yield from $this->buffer->createProducer();
        
        $this->buffer->destroy();
    }
    
    public function streamingFinished(Signal $signal): bool
    {
        if ($this->buffer->count() === 0) {
            return false;
        }
        
        $signal->restartWith($this->buffer->createProducer(), $this->next);
        
        return true;
    }
    
    public function setItemBuffer(ItemBuffer $buffer): void
    {
        $this->buffer = $buffer;
        $this->length = $buffer->getLength();
    }
    
    public function length(): int
    {
        return $this->length;
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->buffer->destroy();
            
            parent::destroy();
        }
    }
}