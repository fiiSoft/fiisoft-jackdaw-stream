<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Collector;

final class ArrayAccess implements Collector
{
    private \ArrayAccess $buffer;
    
    public function __construct(\ArrayAccess $buffer)
    {
        $this->buffer = $buffer;
    }

    public function set($key, $value): void
    {
        $this->buffer[$key] = $value;
    }
    
    public function add($value): void
    {
        $this->buffer[] = $value;
    }
}