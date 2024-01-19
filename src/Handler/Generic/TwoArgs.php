<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Handler\Generic;

use FiiSoft\Jackdaw\Handler\GenericErrorHandler;

final class TwoArgs extends GenericErrorHandler
{
    /**
     * @inheritDoc
     */
    public function handle(\Throwable $error, $key, $value): ?bool
    {
        return ($this->callable)($error, $key);
    }
}