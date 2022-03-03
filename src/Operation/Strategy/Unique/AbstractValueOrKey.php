<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Strategy\Unique;

use FiiSoft\Jackdaw\Internal\Item;

abstract class AbstractValueOrKey implements Strategy
{
    final public function check(Item $item): bool
    {
        $isValueUnique = $this->isUnique($item->value);
    
        if ($isValueUnique && $item->value === $item->key) {
            return true;
        }
    
        $isKeyUnique = $this->isUnique($item->key);
    
        return $isValueUnique || $isKeyUnique;
    }
    
    /**
     * @param mixed $value
     * @return bool
     */
    abstract protected function isUnique($value): bool;
}