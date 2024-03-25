<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Generator\Uuid\Symfony;

final class SymfonyBase58 extends SymfonyUuidGenerator
{
    public function create(): string
    {
        return ($this->factory)()->toBase58();
    }
}