<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Reducer;

final class Product implements Reducer
{
    /** @var float|int */
    private $result = 1;
    
    private bool $hasAny = false;
    
    /**
     * @param float|int $value
     * @return void
     */
    public function consume($value): void
    {
        $this->result *= $value;
        $this->hasAny = true;
    }
    
    /**
     * @return float|int
     */
    public function result()
    {
        return $this->result;
    }
    
    public function hasResult(): bool
    {
        return $this->hasAny;
    }
    
    public function reset(): void
    {
        $this->result = 1;
        $this->hasAny = false;
    }
}