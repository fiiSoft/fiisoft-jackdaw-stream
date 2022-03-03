<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Strategy\Unique;

use FiiSoft\Jackdaw\Internal\Item;

final class ValueAndKeyStandard implements Strategy
{
    /** @var Item[] */
    private array $keysAndValues = [];
    
    public function check(Item $item): bool
    {
        foreach ($this->keysAndValues as $prev) {
            if ($prev->value === $item->value || $prev->key === $item->key
                || $prev->value === $item->key || $prev->key === $item->value
            ) {
                return false;
            }
        }
    
        $this->keysAndValues[] = $item->copy();
        
        return true;
    }
}