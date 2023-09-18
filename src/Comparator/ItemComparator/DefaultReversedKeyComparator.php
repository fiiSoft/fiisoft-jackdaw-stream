<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator\ItemComparator;

use FiiSoft\Jackdaw\Internal\Item;

final class DefaultReversedKeyComparator implements ItemComparator
{
    public function compare(Item $first, Item $second): int
    {
        return \gettype($second->key) <=> \gettype($first->key) ?: $second->key <=> $first->key;
    }
}