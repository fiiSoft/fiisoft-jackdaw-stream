<?php

namespace FiiSoft\Jackdaw\Consumer;

interface Consumer
{
    /**
     * @param mixed $value
     * @param mixed|null $key
     */
    public function consume($value, $key): void;
}