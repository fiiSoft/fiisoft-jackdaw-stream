<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator\ItemComparator;

use FiiSoft\Jackdaw\Internal\Item;

final class DefaultReversedAssocComparator implements ItemComparator
{
    public function compare(Item $first, Item $second): int
    {
        return \gettype($second->value) <=> \gettype($first->value)
            ?: $second->value <=> $first->value
            ?: \gettype($second->key) <=> \gettype($first->key)
            ?: $second->key <=> $first->key;
    }
}