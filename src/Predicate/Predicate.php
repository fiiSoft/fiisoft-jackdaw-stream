<?php

namespace FiiSoft\Jackdaw\Predicate;

use FiiSoft\Jackdaw\Internal\Check;

interface Predicate
{
    /**
     * @param mixed $value
     * @param mixed $key
     */
    public function isSatisfiedBy($value, $key = null, int $mode = Check::VALUE): bool;
}