<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Internal\Dispatcher;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Reducer\Reducer;

final class ReducerAdapter implements Handler
{
    private Reducer $reducer;
    
    public function __construct(Reducer $reducer)
    {
        $this->reducer = $reducer;
    }
    
    public function handle(Signal $signal): void
    {
        $this->reducer->consume($signal->item->value);
    }
}