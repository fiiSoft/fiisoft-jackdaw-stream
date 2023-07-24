<?php

namespace FiiSoft\Jackdaw\Comparator\ItemComparator;

use FiiSoft\Jackdaw\Internal\Item;

interface ItemComparator
{
    public function compare(Item $first, Item $second): int;
}