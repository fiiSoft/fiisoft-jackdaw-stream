<?php

namespace FiiSoft\Jackdaw\Comparator\ItemComparator;

use FiiSoft\Jackdaw\Internal\Item;

interface ItemComparator
{
    /**
     * @return int 0 when the first element is equal to the second, <0 when is smaller, >0 when is greater
     */
    public function compare(Item $first, Item $second): int;
}