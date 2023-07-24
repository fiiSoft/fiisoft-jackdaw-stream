<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class Skip extends BaseOperation
{
    private int $offset;
    private int $count = 0;
    
    public function __construct(int $offset)
    {
        if ($offset < 0) {
            throw new \InvalidArgumentException('Invalid param offset');
        }
        
        $this->offset = $offset;
    }
    
    public function handle(Signal $signal): void
    {
        if ($this->count === $this->offset) {
            $this->next->handle($signal);
            $signal->forget($this);
        } else {
            ++$this->count;
        }
    }
    
    public function mergeWith(Skip $other): void
    {
        $this->offset += $other->offset;
    }
}