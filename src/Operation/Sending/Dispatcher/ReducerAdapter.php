<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Sending\Dispatcher;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Reducer\Reducer;

final class ReducerAdapter extends PrimitiveHandler
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
    
    public function handlePair($value, $key): void
    {
        $this->reducer->consume($value);
    }
}