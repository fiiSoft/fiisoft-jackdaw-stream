<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Generator\Uuid\Symfony;

final class SymfonyDefault extends SymfonyUuidGenerator
{
    public function create(): string
    {
        return ($this->factory)()->toRfc4122();
    }
}