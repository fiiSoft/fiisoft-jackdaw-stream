<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Operation\State\Tail\BufferNotFull;
use FiiSoft\Jackdaw\Operation\State\Tail\State;

final class Tail extends BaseOperation
{
    private \SplFixedArray $buffer;
    private State $state;
    
    public function __construct(int $length)
    {
        if ($length < 1) {
            throw new \InvalidArgumentException('Invalid param length');
        }
    
        $this->buffer = new \SplFixedArray($length);
        $this->state = new BufferNotFull($this, $this->buffer);
    }
    
    public function handle(Signal $signal): void
    {
        $this->state->hold($signal->item);
    }
    
    public function streamingFinished(Signal $signal): void
    {
        if ($this->state->count() > 0) {
            $signal->restartFrom($this->next, $this->state->fetchItems());
        }
    }
    
    public function mergeWith(Tail $other): void
    {
        $this->state->setLength(\min($this->length(), $other->length()));
    }
    
    public function length(): int
    {
        return $this->buffer->getSize();
    }
    
    public function transitTo(State $state): void
    {
        $this->state = $state;
    }
}