<?php

namespace FiiSoft\Jackdaw\Filter;

use FiiSoft\Jackdaw\Internal\Check;

interface Filter
{
    public function isAllowed($value, $key, int $mode = Check::VALUE): bool;
}