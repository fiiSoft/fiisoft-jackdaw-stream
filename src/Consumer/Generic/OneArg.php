<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Consumer\Generic;

use FiiSoft\Jackdaw\Consumer\GenericConsumer;

final class OneArg extends GenericConsumer
{
    /**
     * @inheritDoc
     */
    public function consume($value, $key): void
    {
        ($this->callable)($value);
    }
}