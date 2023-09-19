<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Adapter;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Producer\Tech\NonCountableProducer;
use FiiSoft\Jackdaw\Registry\RegReader;

final class RegistryAdapter extends NonCountableProducer
{
    private RegReader $reader;
    
    private int $index = 0;
    
    public function __construct(RegReader $reader)
    {
        $this->reader = $reader;
    }
    
    public function feed(Item $item): \Generator
    {
        $value = $this->reader->read();
        
        while ($value !== null) {
            $item->key = $this->index++;
            $item->value = $value;
            
            yield;
            
            $value = $this->reader->read();
        }
    }
}