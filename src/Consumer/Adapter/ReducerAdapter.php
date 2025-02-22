<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Consumer\Adapter;

use FiiSoft\Jackdaw\Consumer\Consumer;
use FiiSoft\Jackdaw\Reducer\Reducer;

final class ReducerAdapter implements Consumer
{
    private Reducer $reducer;
    
    public function __construct(Reducer $reducer)
    {
        $this->reducer = $reducer;
    }
    
    /**
     * @inheritDoc
     */
    public function consume($value, $key): void
    {
        $this->reducer->consume($value);
    }
    
    /**
     * @inheritDoc
     */
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            $this->reducer->consume($value);
            
            yield $key => $value;
        }
    }
}