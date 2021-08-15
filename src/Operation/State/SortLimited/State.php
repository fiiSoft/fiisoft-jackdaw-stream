<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\State\SortLimited;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Operation\SortLimited;
use SplHeap;

abstract class State
{
    /** @var SortLimited */
    protected $operation;
    
    /** @var SplHeap */
    protected $buffer;
    
    public function __construct(SortLimited $operation, SplHeap $buffer)
    {
        $this->operation = $operation;
        $this->buffer = $buffer;
    }
    
    abstract public function hold(Item $item);
    
    abstract public function setLength(int $length);
}