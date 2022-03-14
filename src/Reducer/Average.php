<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Reducer;

use FiiSoft\Jackdaw\Internal\Item;

final class Average implements Reducer
{
    private int $count = 0;
    
    /** @var float|int */
    private $total = 0;
    
    private ?int $precision;
    
    public function __construct(?int $roundPrecision = null)
    {
        $this->precision = $roundPrecision;
    }
    
    /**
     * @param float|int $value
     * @return void
     */
    public function consume($value): void
    {
        $this->total += $value;
        
        ++$this->count;
    }
    
    /**
     * @return float|int
     */
    public function result()
    {
        if ($this->precision !== null) {
            return \round($this->total / $this->count, $this->precision);
        }
        
        return $this->total / $this->count;
    }
    
    public function hasResult(): bool
    {
        return $this->count !== 0;
    }
    
    public function getResult(): Item
    {
        return new Item(0, $this->result());
    }
    
    public function reset(): void
    {
        $this->count = 0;
        $this->total = 0;
    }
}