<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\ValueRef\Adapter;

use FiiSoft\Jackdaw\Registry\RegReader;

final class RegistryIntValue extends VolatileIntValue
{
    private RegReader $reader;
    
    public function __construct(RegReader $reader)
    {
        $this->reader = $reader;
    }
    
    public function int(): int
    {
        return $this->reader->read();
    }
}