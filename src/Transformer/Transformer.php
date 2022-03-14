<?php

namespace FiiSoft\Jackdaw\Transformer;

interface Transformer
{
    /**
     * @param mixed $value
     * @param string|int $key
     * @return mixed
     */
    public function transform($value, $key);
}