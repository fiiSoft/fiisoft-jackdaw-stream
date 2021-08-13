<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Strategy\Unique;

use FiiSoft\Jackdaw\Internal\Item;

final class ValueStandard implements Strategy
{
    /** @var array */
    private $valuesMap = [];
    
    /** @var array */
    private $values = [];
    
    public function check(Item $item): bool
    {
        $value = $item->value;
        
        if (\is_int($value) || \is_string($value)) {
            if (isset($this->valuesMap[$value])) {
                return false;
            }
        
            $this->valuesMap[$value] = true;
            return true;
        }
    
        if (\in_array($value, $this->values, true)) {
            return false;
        }
    
        $this->values[] = $value;
        return true;
    }
}