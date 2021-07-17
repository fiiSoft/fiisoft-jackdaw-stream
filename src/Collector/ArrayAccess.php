<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Collector;

final class ArrayAccess implements Collector
{
    /** @var \ArrayAccess */
    private $buffer;
    
    public function __construct(\ArrayAccess $buffer)
    {
        $this->buffer = $buffer;
    }

    public function set($key, $value)
    {
        $this->buffer[$key] = $value;
    }
    
    public function add($value)
    {
        $this->buffer[] = $value;
    }
}