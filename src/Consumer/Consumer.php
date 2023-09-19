<?php

namespace FiiSoft\Jackdaw\Consumer;

use FiiSoft\Jackdaw\Operation\Internal\Dispatcher\HandlerReady;

interface Consumer extends ConsumerReady, HandlerReady
{
    /**
     * @param mixed $value
     * @param mixed|null $key
     */
    public function consume($value, $key): void;
}