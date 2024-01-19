<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Consumer;

final class Idle implements Consumer
{
    /**
     * @inheritDoc
     */
    public function consume($value, $key): void
    {
        //noop
    }
}