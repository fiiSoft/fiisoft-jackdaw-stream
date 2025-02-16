<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Reducer;

final class MinMax implements Reducer
{
    private bool $isFirst = true;
    
    /** @var mixed */
    private $min = null, $max = null;
    
    /**
     * @param mixed $value anything that can be compared using < and > operators
     */
    public function consume($value): void
    {
        if ($this->isFirst) {
            $this->min = $this->max = $value;
            $this->isFirst = false;
        } elseif ($value < $this->min) {
            $this->min = $value;
        } elseif ($value > $this->max) {
            $this->max = $value;
        }
    }
    
    /**
     * @return array{min:float|int, max:float|int}
     */
    public function result(): array
    {
        return [
            'min' => $this->min,
            'max' => $this->max,
        ];
    }
    
    public function reset(): void
    {
        $this->isFirst = true;
    }
    
    public function hasResult(): bool
    {
        return !$this->isFirst;
    }
}