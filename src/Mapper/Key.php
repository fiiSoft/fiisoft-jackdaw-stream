<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Mapper\Internal\StatelessMapper;

final class Key extends StatelessMapper
{
    /**
     * @inheritDoc
     */
    public function map($value, $key = null)
    {
        return $key;
    }
    
    public function mergeWith(Mapper $other): bool
    {
        return $other instanceof self;
    }
}