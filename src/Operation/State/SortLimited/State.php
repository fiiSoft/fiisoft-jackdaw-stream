<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\State\SortLimited;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Operation\SortLimited;
use SplHeap;

abstract class State
{
    protected SortLimited $operation;
    protected SplHeap $buffer;
    
    public function __construct(SortLimited $operation, SplHeap $buffer)
    {
        $this->operation = $operation;
        $this->buffer = $buffer;
    }
    
    abstract public function hold(Item $item): void;
    
    abstract public function setLength(int $length): void;
}