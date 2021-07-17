<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

final class ToString implements Mapper
{
    public function map($value, $key)
    {
        return (string) $value;
    }
}