<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper\Internal;

abstract class StatelessMapper extends BaseMapper
{
    final protected function isStateless(): bool
    {
        return true;
    }
}