<?php

namespace FiiSoft\Jackdaw\Condition;

use FiiSoft\Jackdaw\Discriminator\DiscriminatorReady;

interface Condition extends ConditionReady, DiscriminatorReady
{
    /**
     * @param mixed $value
     * @param mixed $key
     */
    public function isTrueFor($value, $key): bool;
}