<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Reducer;

final class Smallest implements Reducer
{
    /** @var \Countable|array<string|int, mixed>|null */
    private $result = null;
    
    private int $size = \PHP_INT_MAX;
    
    /**
     * @param \Countable|array<string|int, mixed> $value
     */
    public function consume($value): void
    {
        if (\count($value) < $this->size) {
            $this->result = $value;
            $this->size = \count($value);
        }
    }
    
    /**
     * @return \Countable|array<string|int, mixed>|null
     */
    public function result()
    {
        return $this->result;
    }
    
    public function hasResult(): bool
    {
        return $this->result !== null;
    }
    
    public function reset(): void
    {
        $this->result = null;
        $this->size = \PHP_INT_MAX;
    }
}