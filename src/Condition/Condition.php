<?php

namespace FiiSoft\Jackdaw\Condition;

interface Condition
{
    public function isTrueFor($value, $key): bool;
}