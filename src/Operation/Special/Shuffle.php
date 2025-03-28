<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Special;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Collecting\ShuffleAll;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Producer\Internal\ForwardItemsIterator;

abstract class Shuffle extends BaseOperation
{
    /** @var Item[] */
    protected array $items = [];
    
    protected ForwardItemsIterator $iterator;
    
    final public static function create(?int $chunkSize = null): self
    {
        return $chunkSize === null ? new ShuffleAll() : new ShuffleChunks($chunkSize);
    }
    
    protected function __construct()
    {
        $this->iterator = new ForwardItemsIterator();
    }
    
    final public function streamingFinished(Signal $signal): bool
    {
        if (empty($this->items)) {
            return parent::streamingFinished($signal);
        }
    
        \shuffle($this->items);
        
        $signal->restartWith($this->iterator->with($this->items), $this->next);
        $this->reset();
    
        return true;
    }
    
    abstract protected function reset(): void;
    
    abstract public function mergedWith(Shuffle $other): Shuffle;
    
    final protected function __clone()
    {
        $this->iterator = clone $this->iterator;
        
        parent::__clone();
    }
    
    final public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->items = [];
            $this->iterator->destroy();
            
            parent::destroy();
        }
    }
}