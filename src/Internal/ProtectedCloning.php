<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal;

use FiiSoft\Jackdaw\Exception\ImpossibleSituationException;

abstract class ProtectedCloning
{
    protected function __clone()
    {
        throw ImpossibleSituationException::called(__METHOD__);
    }
}