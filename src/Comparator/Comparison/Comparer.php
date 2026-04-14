<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator\Comparison;

use FiiSoft\Jackdaw\Internal\Item;

interface Comparer
{
    public function areDifferent(Item $first, Item $second): bool;
}