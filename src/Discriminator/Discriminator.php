<?php

namespace FiiSoft\Jackdaw\Discriminator;

interface Discriminator
{
    /**
     * @param mixed $value
     * @param mixed $key
     * @return string|int|bool used to classify element to some group; just remember that bool will be casted to int
     */
    public function classify($value, $key);
}