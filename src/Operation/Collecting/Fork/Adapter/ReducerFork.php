<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Collecting\Fork\Adapter;

use FiiSoft\Jackdaw\Operation\Collecting\Fork\ForkHandler;
use FiiSoft\Jackdaw\Reducer\Reducer;

final class ReducerFork implements ForkHandler
{
    private Reducer $reducer;
    
    public function __construct(Reducer $reducer)
    {
        $this->reducer = $reducer;
    }
    
    public function create(): ForkHandler
    {
        return new self(clone $this->reducer);
    }
    
    /**
     * @inheritDoc
     */
    public function accept($value, $key): void
    {
        $this->reducer->consume($value);
    }
    
    public function isEmpty(): bool
    {
        return !$this->reducer->hasResult();
    }
    
    /**
     * @inheritDoc
     */
    public function result()
    {
        return $this->reducer->result();
    }
    
    public function destroy(): void
    {
        $this->reducer->reset();
    }
}