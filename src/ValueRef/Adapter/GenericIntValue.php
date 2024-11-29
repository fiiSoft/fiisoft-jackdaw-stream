<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\ValueRef\Adapter;

final class GenericIntValue extends VolatileIntValue
{
    /** @var callable */
    private $provider;
    
    public function __construct(callable $provider)
    {
        $this->provider = $provider;
    }
    
    public function int(): int
    {
        return ($this->provider)();
    }
}