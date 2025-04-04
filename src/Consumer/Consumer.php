<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Consumer;

use FiiSoft\Jackdaw\Internal\StreamBuilder;
use FiiSoft\Jackdaw\Operation\Internal\DispatchReady;

interface Consumer extends StreamBuilder, ConsumerReady, DispatchReady
{
    /**
     * @param mixed $value
     * @param mixed|null $key
     */
    public function consume($value, $key): void;
}