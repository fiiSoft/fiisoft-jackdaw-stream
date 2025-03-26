<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Terminating;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;

final class Last extends SimpleFinal
{
    private ?Item $item = null;
    
    public function handle(Signal $signal): void
    {
        if ($this->item === null) {
            $this->item = $signal->item;
        }
    }
    
    public function buildStream(iterable $stream): iterable
    {
        $item = new Item();
        $found = false;

        foreach ($stream as $item->key => $item->value) {
            if (!$found) {
                $found = true;
            }
        }

        if ($found) {
            $this->item = $item;
        }

        yield;
    }
    
    public function getResult(): ?Item
    {
        return $this->item;
    }
    
    protected function __clone()
    {
        parent::__clone();
        
        $this->item = null;
    }
    
    public function isReindexed(): bool
    {
        return false;
    }
}