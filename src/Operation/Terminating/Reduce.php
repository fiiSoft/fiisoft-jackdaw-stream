<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Terminating;

use FiiSoft\Jackdaw\Reducer\Reducer;
use FiiSoft\Jackdaw\Reducer\Reducers;
use FiiSoft\Jackdaw\Stream;

final class Reduce extends ReduceFinal
{
    /**
     * @param Stream $stream
     * @param Reducer|callable|array $reducer
     * @param callable|mixed|null $orElse
     */
    public function __construct(Stream $stream, $reducer, $orElse = null)
    {
        $reducer = Reducers::getAdapter($reducer);
        
        parent::__construct($stream, $reducer, $orElse);
    }
}