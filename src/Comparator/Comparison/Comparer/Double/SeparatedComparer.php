<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator\Comparison\Comparer\Double;

use FiiSoft\Jackdaw\Internal\Item;

final class SeparatedComparer extends DoubleComparer
{
    public function areDifferent(Item $first, Item $second): bool
    {
        return $this->valueComparator->compare($first->value, $second->value) !== 0
            && $this->keyComparator->compare($first->key, $second->key) !== 0;
    }
}