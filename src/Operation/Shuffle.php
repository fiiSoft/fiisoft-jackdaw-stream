<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
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
            
            $this->items = [];
            $this->count = 0;
        }
    }
    
    public function streamingFinished(Signal $signal): bool
    {
        if (empty($this->items)) {
            return $this->next->streamingFinished($signal);
        }
    
        \shuffle($this->items);
        $signal->restartWith($this->iterator->with($this->items), $this->next);
    
        $this->items = [];
        $this->count = 0;
    
        return true;
    }
}