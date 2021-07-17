<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

final class ToBool implements Mapper
{
    public function map($value, $key)
    {
        return (bool) $value;
    }
}