<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Reducer;

final class BasicStats implements Reducer
{
    private int $count = 0;
    
    /** @var float|int */
    private $min = null, $max = null, $sum = null;
    
    private ?int $precision;
    
    public function __construct(?int $roundPrecision = null)
    {
        $this->precision = $roundPrecision;
    }
    
    /**
     * @inheritDoc
     */
    public function consume($value): void
    {
        if ($this->count > 0) {
            $this->sum += $value;
            
            if ($value < $this->min) {
                $this->min = $value;
            } elseif ($value > $this->max) {
                $this->max = $value;
            }
        } else {
            $this->min = $this->max = $this->sum = $value;
        }
        
        ++$this->count;
    }
    
    /**
     * @return array{count:int, min:float|int, max:float|int, sum:float|int, avg:float|int}
     */
    public function result(): array
    {
        if ($this->precision !== null) {
            $average = \round($this->sum / $this->count, $this->precision);
            $sum = \is_float($this->sum) ? \round($this->sum, $this->precision) : $this->sum;
            $min = \is_float($this->min) ? \round($this->min, $this->precision) : $this->min;
            $max = \is_float($this->max) ? \round($this->max, $this->precision) : $this->max;
        } else {
            $average = $this->sum / $this->count;
            $sum = $this->sum;
            $min = $this->min;
            $max = $this->max;
        }
        
        return [
            'count' => $this->count,
            'min' => $min,
            'max' => $max,
            'sum' => $sum,
            'avg' => $average,
        ];
    }
    
    public function reset(): void
    {
        $this->count = 0;
    }
    
    public function hasResult(): bool
    {
        return $this->count > 0;
    }
}