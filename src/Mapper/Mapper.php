<?php

namespace FiiSoft\Jackdaw\Mapper;

interface Mapper
{
    /**
     * @param mixed $value
     * @param string|int $key
     * @return mixed
     */
    public function map($value, $key);
}