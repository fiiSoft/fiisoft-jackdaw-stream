<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Reducer\Reducer;
use FiiSoft\Jackdaw\Reducer\Reducers;

final class Scan extends BaseOperation
{
    private Reducer $reducer;
    
    /** @var Item */
    private $previous;
    
    /**
     * @param mixed $initial
     * @param Reducer|callable $reducer
     */
    public function __construct($initial, $reducer)
    {
        $this->reducer = Reducers::getAdapter($reducer);
        $this->previous = new Item(null, $initial);
    }
    
    public function handle(Signal $signal): void
    {
        $this->reducer->consume($this->previous->value, $this->previous->key);
        
        $signal->item->copyTo($this->previous);
        $signal->item->value = $this->reducer->result();
    
        $this->next->handle($signal);
    }
    
    public function streamingFinished(Signal $signal): bool
    {
        //TODO check if it works properly in complex scenarios
        
        $this->handle($signal);
        
        return $this->next->streamingFinished($signal);
    }
}