<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Strategy\Unique;

use FiiSoft\Jackdaw\Internal\Item;

final class ValueStandard implements Strategy
{
    private array $valuesMap = [];
    private array $values = [];
    
    public function check(Item $item): bool
    {
        if (\is_int($item->value) || \is_string($item->value)) {
            if (isset($this->valuesMap[$item->value])) {
                return false;
            }
        
            $this->valuesMap[$item->value] = true;
            return true;
        }
    
        if (\in_array($item->value, $this->values, true)) {
            return false;
        }
    
        $this->values[] = $item->value;
    
        return true;
    }
}