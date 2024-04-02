<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Filtering\Unique\StandardChecker\Double;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Operation\Filtering\Unique\StandardChecker\DoubleChecker;

final class CheckValueAndKey extends DoubleChecker
{
    public function check(Item $item): bool
    {
        if ($this->key->isUnique($item->key) && $this->value->isUnique($item->value)) {
            $this->key->remember($item->key);
            $this->value->remember($item->value);
            
            return true;
        }
        
        return false;
    }
}