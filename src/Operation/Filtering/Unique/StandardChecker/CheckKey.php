<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Filtering\Unique\StandardChecker;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Operation\Filtering\Unique\StandardChecker;

final class CheckKey extends StandardChecker
{
    public function check(Item $item): bool
    {
        if ($this->strategy->isUnique($item->key)) {
            $this->strategy->remember($item->key);
            return true;
        }
        
        return false;
    }
}