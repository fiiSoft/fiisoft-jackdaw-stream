<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Consumer;

final class Counter implements Consumer
{
    private int $count = 0;
    
    public function consume($value, $key): void
    {
        ++$this->count;
    }
    
    public function count(): int
    {
        return $this->count;
    }
    
    /**
     * Alias for count for convenient use.
     */
    public function get(): int
    {
        return $this->count;
    }
}