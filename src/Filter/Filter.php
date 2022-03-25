<?php

namespace FiiSoft\Jackdaw\Filter;

use FiiSoft\Jackdaw\Internal\Check;

interface Filter
{
    /**
     * @param mixed $value
     * @param string|int $key
     * @param int $mode
     * @return bool
     */
    public function isAllowed($value, $key, int $mode = Check::VALUE): bool;
}