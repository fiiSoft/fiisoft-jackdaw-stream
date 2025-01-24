<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Sending\Dispatcher;

use FiiSoft\Jackdaw\Internal\Signal;

interface Handler
{
    public function handle(Signal $signal): void;
    
    /**
     * @param mixed $value
     * @param mixed $key
     */
    public function handlePair($value, $key): void;
}