<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator\ItemComparator;

use FiiSoft\Jackdaw\Internal\Item;

final class CustomNormalAssocComparator extends CustomItemComparator
{
    public function compare(Item $first, Item $second): int
    {
        return $this->comparator->compareAssoc($first->value, $second->value, $first->key, $second->key);
    }
}