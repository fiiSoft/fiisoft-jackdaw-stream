<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\State\SortLimited;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Operation\SortLimited;

abstract class State
{
    protected SortLimited $operation;
    
    public function __construct(SortLimited $operation)
    {
        $this->operation = $operation;
    }
    
    abstract public function hold(Item $item): void;
    
    abstract public function setLength(int $length): void;
    
    abstract public function isEmpty(): bool;
    
    /**
     * @return Item[]
     */
    abstract public function getCollectedItems(): array;
    
    public function destroy(): void
    {
        //noop
    }
}