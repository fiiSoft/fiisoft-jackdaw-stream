<?php

namespace FiiSoft\Jackdaw\Operation\Filtering\Unique;

use FiiSoft\Jackdaw\Internal\Destroyable;
use FiiSoft\Jackdaw\Internal\Item;

interface UniquenessChecker extends Destroyable
{
    /**
     * @return bool true when Item is unique, false if not
     */
    public function check(Item $item): bool;
}