<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator\ItemComparator;

use FiiSoft\Jackdaw\Internal\Item;

final class DefaultReversedKeyComparator implements ItemComparator
{
    public function compare(Item $first, Item $second): int
    {
        return $second->key <=> $first->key;
    }
}