<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Filtering\Unique\StandardChecker\Single;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Operation\Filtering\Unique\StandardChecker\SingleChecker;

final class CheckKey extends SingleChecker
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