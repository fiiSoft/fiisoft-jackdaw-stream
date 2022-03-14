<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Terminating;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\FinalOperation;
use FiiSoft\Jackdaw\Reducer\Reducer;
use FiiSoft\Jackdaw\Reducer\Reducers;
use FiiSoft\Jackdaw\Stream;

final class Fold extends FinalOperation
{
    private Reducer $reducer;
    
    /**
     * @param Stream $stream
     * @param mixed $initial
     * @param Reducer|callable $reducer
     */
    public function __construct(Stream $stream, $initial, $reducer)
    {
        $this->reducer = Reducers::getAdapter($reducer);
        $this->reducer->consume($initial);
        
        parent::__construct($stream, $this->reducer);
    }
    
    public function handle(Signal $signal): void
    {
        $this->reducer->consume($signal->item->value);
    }
}