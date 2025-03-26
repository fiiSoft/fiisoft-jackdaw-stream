<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Terminating;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Reducer\Reducer;
use FiiSoft\Jackdaw\Stream;

abstract class ReduceFinal extends FinalOperation
{
    private Reducer $reducer;
    
    /**
     * @param callable|mixed|null $orElse
     */
    public function __construct(Stream $stream, Reducer $reducer, $orElse = null)
    {
        parent::__construct($stream, $orElse);
        
        $this->reducer = $reducer;
    }
    
    final public function handle(Signal $signal): void
    {
        $this->reducer->consume($signal->item->value);
    }
    
    final public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $value) {
            $this->reducer->consume($value);
        }
        
        yield;
    }
    
    final protected function __clone()
    {
        $this->reducer = clone $this->reducer;
        
        parent::__clone();
    }
    
    final public function getResult(): ?Item
    {
        return $this->reducer->hasResult()
            ? new Item(0, $this->reducer->result())
            : null;
    }
    
    final public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->reducer->reset();
            
            parent::destroy();
        }
    }
    
    final public function isReindexed(): bool
    {
        return true;
    }
}