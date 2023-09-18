<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Generator\Uuid\Ramsey;

final class RamseyDefault extends RamseyUuidGenerator
{
    public function create(): string
    {
        return $this->generator->toString();
    }
}