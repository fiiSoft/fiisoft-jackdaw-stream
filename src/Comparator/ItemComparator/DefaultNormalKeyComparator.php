<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator\ItemComparator;

use FiiSoft\Jackdaw\Internal\Item;

final class DefaultNormalKeyComparator implements ItemComparator
{
    public function compare(Item $first, Item $second): int
    {
        return $first->key <=> $second->key;
    }
}