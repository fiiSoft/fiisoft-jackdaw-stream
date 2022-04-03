<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Strategy\Unique;

use FiiSoft\Jackdaw\Comparator\Comparator;
use FiiSoft\Jackdaw\Internal\Item;

final class ValueComparator implements Strategy
{
    private Comparator $comparator;
    private array $values = [];
    
    public function __construct(Comparator $comparator)
    {
        $this->comparator = $comparator;
    }
    
    public function check(Item $item): bool
    {
        foreach ($this->values as $val) {
            if ($this->comparator->compare($val, $item->value) === 0) {
                return false;
            }
        }
    
        $this->values[] = $item->value;
        
        return true;
    }
}