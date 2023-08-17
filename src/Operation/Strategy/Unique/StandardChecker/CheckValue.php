<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Strategy\Unique\StandardChecker;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Operation\Strategy\Unique\StandardChecker;

final class CheckValue extends StandardChecker
{
    public function check(Item $item): bool
    {
        if ($this->strategy->isUnique($item->value)) {
            $this->strategy->remember($item->value);
            return true;
        }
        
        return false;
    }
}