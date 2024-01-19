<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper\Cast;

use FiiSoft\Jackdaw\Mapper\Internal\BaseMapper;
use FiiSoft\Jackdaw\Mapper\Cast\ToString\ToStringFields;
use FiiSoft\Jackdaw\Mapper\Cast\ToString\ToStringSimple;

abstract class ToString extends BaseMapper
{
    /**
     * @param array|string|int|null $fields
     */
    final public static function create($fields = null): self
    {
        return $fields === null ? new ToStringSimple() : new ToStringFields($fields);
    }
}