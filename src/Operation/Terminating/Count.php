<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Terminating;

use FiiSoft\Jackdaw\Internal\DataAware;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;

final class Count extends SimpleFinal implements DataAware
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
    
    public function setCollectedItems(array $items): void
    {
        $this->count = \count($items);
    }
    
    public function setCollectedData(array $data): void
    {
        $this->count = \count($data);
    }
}