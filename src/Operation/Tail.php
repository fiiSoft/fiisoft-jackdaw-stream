<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Operation\Internal\DataCollector;
use FiiSoft\Jackdaw\Operation\State\ItemBuffer\CircularItemBuffer;
use FiiSoft\Jackdaw\Operation\State\ItemBuffer\ItemBuffer;
use FiiSoft\Jackdaw\Operation\State\ItemBuffer\ItemBufferClient;
use FiiSoft\Jackdaw\Producer\Producer;

final class Tail extends BaseOperation implements DataCollector, ItemBufferClient
{
    private ItemBuffer $buffer;
    private int $length;
    
    public function __construct(int $length)
    {
        if ($length < 1) {
            throw new \InvalidArgumentException('Invalid param length');
        }
        
        $this->length = $length;
        
        $this->prepareBuffer($this->length);
    }
    
    protected function __clone()
    {
        parent::__clone();
        
        $this->prepareBuffer($this->length);
    }
    
    public function mergeWith(Tail $other): void
    {
        $this->prepareBuffer(\min($this->length(), $other->length()));
    }
    
    private function prepareBuffer(int $size): void
    {
        $this->buffer = CircularItemBuffer::initial($this, $size);
    }
    
    public function handle(Signal $signal): void
    {
        $this->buffer->hold($signal->item);
    }
    
    public function streamingFinished(Signal $signal): bool
    {
        if ($this->buffer->count() === 0) {
            return false;
        }
        
        $producer = $this->buffer->createProducer();
        
        if ($this->next instanceof DataCollector) {
            $signal->continueFrom($this->next);
            
            return $this->next->collectDataFromProducer($producer, $signal, false);
        }
        
        $signal->restartWith($producer, $this->next);
        
        return true;
    }
    
    public function collectDataFromProducer(Producer $producer, Signal $signal, bool $reindexed): bool
    {
        $item = $signal->item;
        
        foreach ($producer->feed($item) as $_) {
            $this->buffer->hold($item);
        }
        
        return $this->streamingFinished($signal);
    }
    
    public function acceptSimpleData(array $data, Signal $signal, bool $reindexed): bool
    {
        if (!empty($data)) {
            $tail = \array_slice($data, -$this->length(), null, true);
            
            if ($this->next instanceof DataCollector) {
                $last = \array_key_last($tail);
                $signal->item->key = $last;
                $signal->item->value = $tail[$last];
                
                $signal->continueFrom($this->next);
                
                return $this->next->acceptSimpleData($tail, $signal, $reindexed);
            }
            
            $item = $signal->item;
            foreach ($tail as $item->key => $item->value) {
                $this->buffer->hold($item);
            }
        }
        
        return $this->streamingFinished($signal);
    }
    
    /**
     * @param Item[] $items
     */
    public function acceptCollectedItems(array $items, Signal $signal, bool $reindexed): bool
    {
        if (!empty($items)) {
            $tail = \array_slice($items, -$this->length(), null, true);
            
            if ($this->next instanceof DataCollector) {
                $last = $tail[\array_key_last($tail)];
                $signal->item->key = $last->key;
                $signal->item->value = $last->value;
                
                $signal->continueFrom($this->next);
                
                return $this->next->acceptCollectedItems($tail, $signal, $reindexed);
            }
            
            foreach ($tail as $item) {
                $this->buffer->hold($item);
            }
        }
        
        return $this->streamingFinished($signal);
    }
    
    public function setItemBuffer(ItemBuffer $buffer): void
    {
        $this->buffer = $buffer;
    }
    
    public function length(): int
    {
        return $this->buffer->getLength();
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->buffer->destroy();
            
            parent::destroy();
        }
    }
}