<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Filtering\Unique\StandardChecker\Single;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Operation\Filtering\Unique\StandardChecker\SingleChecker;

final class CheckValue extends SingleChecker
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