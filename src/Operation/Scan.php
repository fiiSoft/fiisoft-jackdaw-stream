<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Reducer\Reducer;
use FiiSoft\Jackdaw\Reducer\Reducers;

final class Scan extends BaseOperation
{
    private Reducer $reducer;
    
    /** @var mixed */
    private $previous;
    
    /**
     * @param mixed $initial
     * @param Reducer|callable $reducer
     */
    public function __construct($initial, $reducer)
    {
        $this->reducer = Reducers::getAdapter($reducer);
        $this->previous = $initial;
    }
    
    public function handle(Signal $signal): void
    {
        $this->reducer->consume($this->previous);
        $this->previous = $signal->item->value;
        
        $signal->item->value = $this->reducer->result();
    
        $this->next->handle($signal);
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
        
        parent::__clone();
    }
}