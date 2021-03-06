<?php

namespace FiiSoft\Jackdaw\Producer;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Stream;

interface Producer
{
    public function feed(Item $item): \Generator;
    
    public function stream(): Stream;
}