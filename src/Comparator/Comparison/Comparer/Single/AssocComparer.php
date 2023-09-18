<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator\Comparison\Comparer\Single;

use FiiSoft\Jackdaw\Internal\Item;

final class AssocComparer extends SingleComparer
{
    public function areDifferent(Item $first, Item $second): bool
    {
        return $this->comparator->compareAssoc($first->value, $second->value, $first->key, $second->key) !== 0;
    }
}