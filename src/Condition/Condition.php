<?php

namespace FiiSoft\Jackdaw\Condition;

interface Condition
{
    /**
     * @param mixed $value
     * @param mixed $key
     */
    public function isTrueFor($value, $key): bool;
}