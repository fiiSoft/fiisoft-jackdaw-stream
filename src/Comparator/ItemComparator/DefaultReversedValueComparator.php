<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator\ItemComparator;

use FiiSoft\Jackdaw\Internal\Item;

final class DefaultReversedValueComparator implements ItemComparator
{
    public function compare(Item $first, Item $second): int
    {
        return $second->value <=> $first->value;
    }
}