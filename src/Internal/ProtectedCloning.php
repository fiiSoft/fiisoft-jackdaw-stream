<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal;

abstract class ProtectedCloning
{
    protected function __clone()
    {
        throw new \BadMethodCallException('Method '.__METHOD__.' should never be called');
    }
}