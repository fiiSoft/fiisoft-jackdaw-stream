<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Terminating;

use FiiSoft\Jackdaw\Reducer\Reducer;
use FiiSoft\Jackdaw\Reducer\Reducers;
use FiiSoft\Jackdaw\Stream;

final class Fold extends ReduceFinal
{
    /**
     * @param Stream $stream
     * @param mixed $initial
     * @param Reducer|callable $reducer Callable accepts two arguments: accumulator and current value
     */
    public function __construct(Stream $stream, $initial, $reducer)
    {
        $reducer = Reducers::getAdapter($reducer);
        $reducer->consume($initial);
        
        parent::__construct($stream, $reducer);
    }
}