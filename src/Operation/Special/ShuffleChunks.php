<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Special;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\Shuffle;

final class ShuffleChunks extends Shuffle
{
    private int $chunkSize;
    private int $count = 0;
    
    protected function __construct(int $chunkSize)
    {
        if ($chunkSize > 1) {
            $this->chunkSize = $chunkSize;
        } else {
            throw InvalidParamException::describe('chunkSize', $chunkSize);
        }
        
        parent::__construct();
    }
    
    public function handle(Signal $signal): void
    {
        $this->items[] = clone $signal->item;
        
        if (++$this->count === $this->chunkSize) {
            \shuffle($this->items);
            $signal->continueWith($this->iterator->with($this->items), $this->next);
            
            $this->items = [];
            $this->count = 0;
        }
    }
    
    public function buildStream(iterable $stream): iterable
    {
        $item = new Item();
        
        foreach ($stream as $item->key => $item->value) {
            $this->items[] = clone $item;
            
            if (++$this->count === $this->chunkSize) {
                \shuffle($this->items);
                
                foreach ($this->items as $item) {
                    yield $item->key => $item->value;
                }
                
                $this->items = [];
                $this->count = 0;
            }
        }
        
        if (empty($this->items)) {
            return [];
        }
        
        \shuffle($this->items);
        
        foreach ($this->items as $item) {
            yield $item->key => $item->value;
        }
        
        $this->reset();
    }
    
    public function mergedWith(Shuffle $other): Shuffle
    {
        return $other instanceof self
            ? self::create(\max($this->chunkSize, $other->chunkSize))
            : self::create();
    }
    
    protected function reset(): void
    {
        $this->items = [];
        $this->count = 0;
    }
}