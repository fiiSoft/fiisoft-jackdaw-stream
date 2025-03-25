<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Sending\Dispatcher;

use FiiSoft\Jackdaw\Internal\Signal;

interface DispatchHandler
{
    public function handle(Signal $signal): void;
    
    /**
     * @param mixed $value
     * @param mixed $key
     */
    public function handlePair($value, $key): void;
    
    public function prepare(): void;
    
    public function dispatchFinished(): void;
}