<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Sending\Dispatcher\Adapter;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Reducer\Reducer;

final class ReducerAdapter extends PrimitiveDispatchHandler
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
    
    /**
     * @inheritDoc
     */
    public function handlePair($value, $key): void
    {
        $this->reducer->consume($value);
    }
}