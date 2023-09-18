<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Generator\Uuid\Ramsey;

final class RamseyHex extends RamseyUuidGenerator
{
    public function create(): string
    {
        return $this->generator->getHex()->toString();
    }
}