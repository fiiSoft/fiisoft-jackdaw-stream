<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Collector\Adapter;

use FiiSoft\Jackdaw\Collector\BaseCollector;

final class ArrayAccessAdapter extends BaseCollector
{
    /** @var \ArrayAccess<string|int, mixed> */
    private \ArrayAccess $buffer;
    
    /**
     * @param \ArrayAccess<string|int, mixed> $buffer
     */
    public function __construct(\ArrayAccess $buffer, ?bool $allowKeys = true)
    {
        parent::__construct($allowKeys);
        
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