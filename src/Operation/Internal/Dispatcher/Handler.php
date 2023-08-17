<?php

namespace FiiSoft\Jackdaw\Operation\Internal\Dispatcher;

use FiiSoft\Jackdaw\Internal\Signal;

interface Handler
{
    public function handle(Signal $signal): void;
}