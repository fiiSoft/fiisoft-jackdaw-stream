<?php

namespace FiiSoft\Jackdaw\Consumer;

interface Consumer
{
    public function consume($value, $key);
}