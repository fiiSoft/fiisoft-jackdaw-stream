<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Terminating;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;

final class Count extends SimpleFinal
{
    private int $count = 0;
    
    public function handle(Signal $signal): void
    {
        ++$this->count;
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $_) {
            ++$this->count;
        }
        
        yield;
    }
    
    public function getResult(): Item
    {
        return new Item(0, $this->count);
    }
    
    public function isReindexed(): bool
    {
        return true;
    }
}