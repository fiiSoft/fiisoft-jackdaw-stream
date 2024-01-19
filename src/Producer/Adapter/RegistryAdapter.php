<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Adapter;

use FiiSoft\Jackdaw\Producer\Tech\BaseProducer;
use FiiSoft\Jackdaw\Registry\RegReader;

final class RegistryAdapter extends BaseProducer
{
    private RegReader $reader;
    
    private int $index = 0;
    
    public function __construct(RegReader $reader)
    {
        $this->reader = $reader;
    }
    
    public function getIterator(): \Generator
    {
        $value = $this->reader->read();
        
        while ($value !== null) {
            yield $this->index++ => $value;
            
            $value = $this->reader->read();
        }
    }
}