<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Operation\Internal\DataCollector;
use FiiSoft\Jackdaw\Producer\Internal\ForwardItemsIterator;

final class Shuffle extends BaseOperation
{
    /** @var Item[] */
    private array $items = [];
    
    private int $chunkSize;
    private int $count = 0;
    
    private ForwardItemsIterator $iterator;
    
    public function __construct(?int $chunkSize = null)
    {
        if ($chunkSize === null) {
            $this->chunkSize = 0;
        } elseif ($chunkSize > 1) {
            $this->chunkSize = $chunkSize;
        } else {
            throw new \InvalidArgumentException('Invalid param chunkSize');
        }
        
        $this->iterator = new ForwardItemsIterator();
    }
    
    public function handle(Signal $signal): void
    {
        $this->items[] = $signal->item->copy();
        
        if ($this->chunkSize !== 0 && ++$this->count === $this->chunkSize) {
            \shuffle($this->items);
            $signal->continueWith($this->iterator->with($this->items), $this->next);
            
            $this->reset();
        }
    }
    
    public function streamingFinished(Signal $signal): bool
    {
        if (empty($this->items)) {
            return parent::streamingFinished($signal);
        }
    
        \shuffle($this->items);
        
        try {
            if ($this->next instanceof DataCollector) {
                $signal->continueFrom($this->next);
                
                return $this->next->acceptCollectedItems($this->items, $signal, false);
            }
            
            $signal->restartWith($this->iterator->with($this->items), $this->next);
        } finally {
            $this->reset();
        }
    
        return true;
    }
    
    private function reset(): void
    {
        $this->items = [];
        $this->count = 0;
    }
    
    public function mergeWith(Shuffle $other): void
    {
        if ($this->chunkSize !== 0) {
            $this->chunkSize = $other->chunkSize !== 0 ? \max($this->chunkSize, $other->chunkSize) : 0;
        }
    }
    
    public function isChunked(): bool
    {
        return $this->chunkSize > 0;
    }
    
    protected function __clone()
    {
        $this->iterator = clone $this->iterator;
        
        parent::__clone();
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->items = [];
            $this->iterator->destroy();
            
            parent::destroy();
        }
    }
}