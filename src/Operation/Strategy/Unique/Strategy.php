<?php

namespace FiiSoft\Jackdaw\Operation\Strategy\Unique;

use FiiSoft\Jackdaw\Internal\Item;

interface Strategy
{
    public function check(Item $item): bool;
}