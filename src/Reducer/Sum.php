<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Reducer;

final class Sum implements Reducer
{
    /** @var float|int */
    private $result = 0;
    
    private bool $hasAny = false;
    
    /**
     * @param float|int $value
     * @return void
     */
    public function consume($value): void
    {
        $this->hasAny = true;
        $this->result += $value;
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
        $this->result = 0;
        $this->hasAny = false;
    }
}