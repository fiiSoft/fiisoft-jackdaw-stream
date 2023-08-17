<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Reducer;

use FiiSoft\Jackdaw\Reducer\Internal\BaseReducer;

final class MinMax extends BaseReducer
{
    private ?int $min = null, $max = null;
    
    public function consume($value): void
    {
        if ($this->min === null) {
            $this->min = $this->max = $value;
        } else {
            if ($value < $this->min) {
                $this->min = $value;
            }
            if ($value > $this->max) {
                $this->max = $value;
            }
        }
    }
    
    public function result(): array
    {
        return [
            'min' => $this->min,
            'max' => $this->max,
        ];
    }
    
    public function reset(): void
    {
        $this->min = $this->max = null;
    }
    
    public function hasResult(): bool
    {
        return $this->min !== null;
    }
}