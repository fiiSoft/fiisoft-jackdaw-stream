<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Operation\Internal\DataCollector;
use FiiSoft\Jackdaw\Operation\State\Tail\BufferNotFull;
use FiiSoft\Jackdaw\Operation\State\Tail\State;
use FiiSoft\Jackdaw\Producer\Producer;

final class Tail extends BaseOperation implements DataCollector
{
    private \SplFixedArray $buffer;
    private State $state;
    
    public function __construct(int $length)
    {
        if ($length < 1) {
            throw new \InvalidArgumentException('Invalid param length');
        }
        
        $this->buffer = new \SplFixedArray($length);
        
        $this->initializeState();
    }
    
    private function initializeState(): void
    {
        $this->state = new BufferNotFull($this, $this->buffer);
    }
    
    public function handle(Signal $signal): void
    {
        $this->state->hold($signal->item);
    }
    
    public function streamingFinished(Signal $signal): bool
    {
        if ($this->state->count() === 0) {
            return false;
        }
        
        $producer = $this->state->bufferIterator();
        
        if ($this->next instanceof DataCollector) {
            $signal->continueFrom($this->next);
            
            return $this->next->collectDataFromProducer($producer, $signal, false);
        }
        
        $signal->restartWith($producer, $this->next);
        
        return true;
    }
    
    public function mergeWith(Tail $other): void
    {
        $this->state->setLength(\min($this->length(), $other->length()));
    }
    
    public function length(): int
    {
        return $this->buffer->getSize();
    }
    
    public function transitTo(State $state): void
    {
        $this->state = $state;
    }
    
    protected function __clone()
    {
        $this->buffer = clone $this->buffer;
        
        parent::__clone();
        
        $this->initializeState();
    }
    
    public function collectDataFromProducer(Producer $producer, Signal $signal, bool $reindexed): bool
    {
        $item = $signal->item;
        
        foreach ($producer->feed($item) as $_) {
            $this->state->hold($item);
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
                $this->state->hold($item);
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
                $this->state->hold($item);
            }
        }
        
        return $this->streamingFinished($signal);
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->state->destroy();
            $this->buffer->setSize(0);
            
            parent::destroy();
        }
    }
}