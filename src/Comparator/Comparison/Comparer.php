<?php

namespace FiiSoft\Jackdaw\Comparator\Comparison;

use FiiSoft\Jackdaw\Internal\Item;

interface Comparer
{
    public function areDifferent(Item $first, Item $second): bool;
}