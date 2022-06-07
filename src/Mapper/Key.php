<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Mapper\Internal\BaseMapper;

final class Key extends BaseMapper
{
    public function map($value, $key)
    {
        return $key;
    }
    
    public function mergeWith(Mapper $other): bool
    {
        return $other instanceof self;
    }
}