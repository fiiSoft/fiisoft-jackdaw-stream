<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator\Comparison\Comparer\Single;

use FiiSoft\Jackdaw\Internal\Item;

final class KeyComparer extends SingleComparer
{
    public function areDifferent(Item $first, Item $second): bool
    {
        return $this->comparator->compare($first->key, $second->key) !== 0;
    }
}