<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Terminating;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;

final class CollectKeys extends SimpleFinal
{
    /** @var array<int, mixed> */
    private array $collected = [];
    
    public function handle(Signal $signal): void
    {
        $this->collected[] = $signal->item->key;
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $_) {
            $this->collected[] = $key;
        }
        
        yield;
    }
    
    public function hasResult(): bool
    {
        return true;
    }
    
    public function getResult(): Item
    {
        return new Item(0, $this->collected);
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->collected = [];
            
            parent::destroy();
        }
    }
}