<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Mapping;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Reducer\Reducer;
use FiiSoft\Jackdaw\Reducer\Reducers;

final class Scan extends BaseOperation
{
    private Reducer $reducer;
    
    private ?Item $previous = null;
    
    /** @var mixed */
    private $initial;
    
    /**
     * @param mixed $initial
     * @param Reducer|callable $reducer
     */
    public function __construct($initial, $reducer)
    {
        $this->reducer = Reducers::getAdapter($reducer);
        $this->initial = $initial;
    }
    
    public function handle(Signal $signal): void
    {
        $this->reducer->consume($this->initial);
        $this->initial = $signal->item->value;
        
        $signal->item->value = $this->reducer->result();
    
        $this->next->handle($signal);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($this->previous === null) {
                $this->reducer->consume($this->initial);
                $this->previous = new Item($key, $value);
            } else {
                $this->reducer->consume($this->previous->value);
                
                $this->previous->key = $key;
                $this->previous->value = $value;
            }
            
            yield $key => $this->reducer->result();
        }
        
        if ($this->previous === null) {
            $this->reducer->consume($this->initial);
            
            yield 0 => $this->reducer->result();
        } else {
            $this->reducer->consume($this->previous->value);
            
            yield $this->previous->key => $this->reducer->result();
            
            $this->previous = null;
        }
    }
    
    public function streamingFinished(Signal $signal): bool
    {
        if ($signal->isEmpty) {
            $this->handle($signal);
        }
        
        return parent::streamingFinished($signal);
    }
    
    protected function __clone()
    {
        $this->reducer = clone $this->reducer;
        $this->previous = null;
        
        parent::__clone();
    }
}