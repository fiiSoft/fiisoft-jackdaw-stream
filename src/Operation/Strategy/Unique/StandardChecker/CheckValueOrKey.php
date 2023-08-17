<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Strategy\Unique\StandardChecker;

use FiiSoft\Jackdaw\Internal\Item;

final class CheckValueOrKey extends DoubleChecker
{
    public function check(Item $item): bool
    {
        if ($this->keyStrategy->isUnique($item->key)) {
            $this->keyStrategy->remember($item->key);
            
            if ($this->strategy->isUnique($item->value)) {
                $this->strategy->remember($item->value);
            }
            
            return true;
        }
        
        if ($this->strategy->isUnique($item->value)) {
            $this->strategy->remember($item->value);
            
            return true;
        }
        
        return false;
    }
}