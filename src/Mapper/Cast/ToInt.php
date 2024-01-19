<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper\Cast;

use FiiSoft\Jackdaw\Mapper\Internal\BaseMapper;
use FiiSoft\Jackdaw\Mapper\Cast\ToInt\ToIntFields;
use FiiSoft\Jackdaw\Mapper\Cast\ToInt\ToIntSimple;

abstract class ToInt extends BaseMapper
{
    /**
     * @param array|string|int|null $fields
     */
    final public static function create($fields = null): self
    {
        return $fields === null ? new ToIntSimple() : new ToIntFields($fields);
    }
}