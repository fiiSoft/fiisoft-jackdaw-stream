<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper\Internal;

use FiiSoft\Jackdaw\Mapper\Mapper;

abstract class BaseMapper implements Mapper
{
    /**
     * @inheritDoc
     */
    public function mergeWith(Mapper $other): bool
    {
        return false;
    }
}