<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Collecting\Fork\Adapter;

use FiiSoft\Jackdaw\Memo\SequenceMemo;
use FiiSoft\Jackdaw\Operation\Collecting\Fork\ForkHandler;

final class SequenceFork implements ForkHandler
{
    private SequenceMemo $sequence;
    
    public function __construct(SequenceMemo $sequence)
    {
        $this->sequence = $sequence;
    }
    
    public function create(): ForkHandler
    {
        return new self(clone $this->sequence);
    }
    
    final public function prepare(): void
    {
        //noop
    }
    
    /**
     * @inheritDoc
     */
    public function accept($value, $key): void
    {
        $this->sequence->write($value, $key);
    }
    
    public function isEmpty(): bool
    {
        return $this->sequence->isEmpty();
    }
    
    /**
     * @return array<string|int, mixed>
     */
    public function result(): array
    {
        return $this->sequence->toArray();
    }
    
    public function destroy(): void
    {
        $this->sequence->clear();
    }
}