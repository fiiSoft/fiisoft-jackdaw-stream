<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Strategy\Unique;

use FiiSoft\Jackdaw\Comparator\Comparator;

final class ValueOrKeyComparator extends AbstractValueOrKey
{
    private Comparator $comparator;
    private array $values = [];
    
    public function __construct(Comparator $comparator)
    {
        $this->comparator = $comparator;
    }
    
    protected function isUnique($value): bool
    {
        foreach ($this->values as $val) {
            if ($this->comparator->compare($val, $value) === 0) {
                return false;
            }
        }
    
        $this->values[] = $value;
        
        return true;
    }
}