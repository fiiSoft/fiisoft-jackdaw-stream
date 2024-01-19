<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper\Cast;

use FiiSoft\Jackdaw\Mapper\Internal\BaseMapper;
use FiiSoft\Jackdaw\Mapper\Cast\ToFloat\ToFloatFields;
use FiiSoft\Jackdaw\Mapper\Cast\ToFloat\ToFloatSimple;

abstract class ToFloat extends BaseMapper
{
    /**
     * @param array|string|int|null $fields
     */
    final public static function create($fields = null): self
    {
        return $fields === null ? new ToFloatSimple() : new ToFloatFields($fields);
    }
}