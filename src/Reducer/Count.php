<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Reducer;

final class Count implements Reducer
{
    private int $count = 0;
    
    public function consume($value): void
    {
        ++$this->count;
    }
    
    public function result(): int
    {
        return $this->count;
    }
    
    public function reset(): void
    {
        $this->count = 0;
    }
    
    public function hasResult(): bool
    {
        return true;
    }
}