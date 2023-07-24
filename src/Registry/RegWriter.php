<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Registry;

use FiiSoft\Jackdaw\Internal\Item;

interface RegWriter
{
    public function remember(Item $item): void;
}