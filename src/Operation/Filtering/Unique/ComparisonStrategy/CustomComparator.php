<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Filtering\Unique\ComparisonStrategy;

use FiiSoft\Jackdaw\Comparator\Comparator;
use FiiSoft\Jackdaw\Operation\Filtering\Unique\ComparisonStrategy;

final class CustomComparator extends ComparisonStrategy
{
    private Comparator $comparator;
    
    /** @var array<int, mixed> */
    private array $values = [];
    
    private int $count = 0, $last = 0;
    
    public function __construct(Comparator $comparator)
    {
        $this->comparator = $comparator;
    }
    
    public function isUnique($value): bool
    {
        $this->last = $left = 0;
        $right = $this->count - 1;
        
        while ($left <= $right) {
            $index = (int) (($left + $right) / 2);
            
            $compare = $this->comparator->compare($value, $this->values[$index]);
            if ($compare > 0) {
                $left = $index + 1;
                $this->last = $left;
            } elseif ($compare < 0) {
                $right = $index - 1;
            } else {
                return false;
            }
        }
        
        return true;
    }
    
    public function remember($value): void
    {
        if ($this->last < $this->count) {
            for ($i = $this->count; $i > $this->last; --$i) {
                $this->values[$i] = $this->values[$i - 1];
            }
            
            $this->values[$this->last] = $value;
        } else {
            $this->values[] = $value;
        }
        
        ++$this->count;
    }
    
    public function destroy(): void
    {
        $this->values = [];
        $this->count = $this->last = 0;
    }
}