<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator\ItemComparator;

use FiiSoft\Jackdaw\Internal\Item;

final class DefaultNormalValueComparator implements ItemComparator
{
    public function compare(Item $first, Item $second): int
    {
        return \gettype($first->value) <=> \gettype($second->value) ?: $first->value <=> $second->value;
    }
}