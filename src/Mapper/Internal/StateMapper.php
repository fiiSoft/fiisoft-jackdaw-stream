<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper\Internal;

abstract class StateMapper extends BaseMapper
{
    final protected function isStateless(): bool
    {
        return false;
    }
}