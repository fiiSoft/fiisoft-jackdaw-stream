<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Mapper\Internal\StatelessMapper;

final class Value extends StatelessMapper
{
    public function map($value, $key)
    {
        return $value;
    }
    
    public function mergeWith(Mapper $other): bool
    {
        return $other instanceof self;
    }
}