<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Terminating;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;

final class First extends SimpleFinal
{
    private ?Item $item = null;
    
    public function handle(Signal $signal): void
    {
        $this->item = clone $signal->item;
        
        $signal->stop();
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            $this->item = new Item($key, $value);
            break;
        }
        
        yield;
    }
    
    public function hasResult(): bool
    {
        return $this->item !== null;
    }
    
    public function getResult(): Item
    {
        return $this->item;
    }
    
    protected function __clone()
    {
        parent::__clone();
        
        $this->item = null;
    }
}