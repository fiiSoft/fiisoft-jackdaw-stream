<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Terminating;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\FinalOperation;
use FiiSoft\Jackdaw\Reducer\Reducer;
use FiiSoft\Jackdaw\Reducer\Reducers;
use FiiSoft\Jackdaw\Stream;

final class Reduce extends FinalOperation
{
    /** @var Reducer */
    private $reducer;
    
    /**
     * @param Stream $stream
     * @param Reducer|callable $reducer
     * @param mixed|null $orElse
     */
    public function __construct(Stream $stream, $reducer, $orElse = null)
    {
        $this->reducer = Reducers::getAdapter($reducer);
        
        parent::__construct($stream, $this->reducer, $orElse);
    }
    
    public function handle(Signal $signal)
    {
        $this->reducer->consume($signal->item->value, $signal->item->key);
    }
}