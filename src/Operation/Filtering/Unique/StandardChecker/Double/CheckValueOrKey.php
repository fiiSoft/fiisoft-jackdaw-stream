<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Filtering\Unique\StandardChecker\Double;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Operation\Filtering\Unique\StandardChecker\DoubleChecker;

final class CheckValueOrKey extends DoubleChecker
{
    public function check(Item $item): bool
    {
        if ($this->key->isUnique($item->key)) {
            $this->key->remember($item->key);
            
            if ($this->value->isUnique($item->value)) {
                $this->value->remember($item->value);
            }
            
            return true;
        }
        
        if ($this->value->isUnique($item->value)) {
            $this->value->remember($item->value);
            
            return true;
        }
        
        return false;
    }
}