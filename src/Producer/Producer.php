<?php

namespace FiiSoft\Jackdaw\Producer;

use FiiSoft\Jackdaw\Internal\Item;

interface Producer
{
    public function feed(Item $item): \Generator;
}