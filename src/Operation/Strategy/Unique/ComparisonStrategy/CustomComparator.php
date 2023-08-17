<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Strategy\Unique\ComparisonStrategy;

use FiiSoft\Jackdaw\Comparator\Comparator;
use FiiSoft\Jackdaw\Operation\Strategy\Unique\ComparisonStrategy;

final class CustomComparator extends ComparisonStrategy
{
    private Comparator $comparator;
    private array $values = [];
    
    public function __construct(Comparator $comparator)
    {
        $this->comparator = $comparator;
    }
    
    public function isUnique($value): bool
    {
        foreach ($this->values as $val) {
            if ($this->comparator->compare($val, $value) === 0) {
                return false;
            }
        }
        
        return true;
    }
    
    public function remember($value): void
    {
        $this->values[] = $value;
    }
    
    public function destroy(): void
    {
        $this->values = [];
    }
}