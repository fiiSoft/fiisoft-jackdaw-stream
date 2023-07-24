<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator\ItemComparator;

use FiiSoft\Jackdaw\Internal\Item;

final class CustomNormalKeyComparator extends CustomItemComparator
{
    public function compare(Item $first, Item $second): int
    {
        return $this->comparator->compare($first->key, $second->key);
    }
}