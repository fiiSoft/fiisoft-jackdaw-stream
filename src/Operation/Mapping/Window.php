<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Mapping;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Operation\Internal\ItemBuffer\CircularItemBuffer;
use FiiSoft\Jackdaw\Operation\Internal\ItemBuffer\ItemBuffer;
use FiiSoft\Jackdaw\Operation\Internal\ItemBuffer\ItemBufferClient;

final class Window extends BaseOperation implements ItemBufferClient
{
    private ItemBuffer $buffer;
    
    private int $size;
    private int $step;
    
    private int $index = -1;
    private int $count = 0;
    
    private bool $notFullYet = true;
    private bool $reindex;
    
    public function __construct(int $size, int $step = 1, bool $reindex = false)
    {
        if ($size < 1) {
            throw InvalidParamException::describe('size', $size);
        }
        
        if ($step < 1) {
            throw InvalidParamException::describe('step', $step);
        }
        
        $this->size = $size;
        $this->step = $step;
        $this->reindex = $reindex;
        
        $this->initializeState();
    }
    
    protected function __clone()
    {
        parent::__clone();
        
        $this->initializeState();
    }
    
    private function initializeState(): void
    {
        $this->buffer = CircularItemBuffer::initial($this, $this->size);
    }
    
    public function handle(Signal $signal): void
    {
        $this->buffer->hold($signal->item);
        
        if (++$this->count === $this->size) {
            $this->count -= $this->step;
            
            if ($this->notFullYet) {
                $this->notFullYet = false;
            }
            
            $signal->item->key = ++$this->index;
            $signal->item->value = $this->buffer->fetchData($this->reindex);
            
            $this->next->handle($signal);
        }
    }
    
    public function buildStream(iterable $stream): iterable
    {
        $item = new Item();
        
        foreach ($stream as $item->key => $item->value) {
            $this->buffer->hold($item);
            
            if (++$this->count === $this->size) {
                $this->count -= $this->step;
                
                if ($this->notFullYet) {
                    $this->notFullYet = false;
                }
                
                yield ++$this->index => $this->buffer->fetchData($this->reindex);
            }
        }
        
        if ($this->count > 0 && ($this->notFullYet || $this->size - $this->count < $this->step)) {
            $value = $this->buffer->fetchData(
                $this->reindex,
                $this->notFullYet ? 0 : $this->size - $this->count
            );
            
            $this->count = 0;
            $this->buffer->clear();
            
            yield ++$this->index => $value;
        }
    }
    
    public function streamingFinished(Signal $signal): bool
    {
        if ($signal->isEmpty
            && $this->count > 0
            && ($this->notFullYet || $this->size - $this->count < $this->step)
        ) {
            $signal->resume();
            
            $signal->item->key = ++$this->index;
            $signal->item->value = $this->buffer->fetchData(
                $this->reindex,
                $this->notFullYet ? 0 : $this->size - $this->count
            );
            
            $this->count = 0;
            $this->buffer->clear();
            
            $this->next->handle($signal);
            
            return true;
        }
        
        return parent::streamingFinished($signal);
    }
    
    public function size(): int
    {
        return $this->size;
    }
    
    public function reindex(): bool
    {
        return $this->reindex;
    }
    
    public function isLikeChunk(): bool
    {
        return $this->size === $this->step;
    }
    
    public function setItemBuffer(ItemBuffer $buffer): void
    {
        $this->buffer = $buffer;
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->isDestroying = true;
            
            $this->buffer->destroy();
            
            parent::destroy();
        }
    }
}