<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper\Cast;

use FiiSoft\Jackdaw\Mapper\Internal\BaseMapper;
use FiiSoft\Jackdaw\Mapper\Cast\ToBool\ToBoolFields;
use FiiSoft\Jackdaw\Mapper\Cast\ToBool\ToBoolSimple;

abstract class ToBool extends BaseMapper
{
    /**
     * @param array|string|int|null $fields
     */
    final public static function create($fields = null): self
    {
        return $fields === null ? new ToBoolSimple() : new ToBoolFields($fields);
    }
}