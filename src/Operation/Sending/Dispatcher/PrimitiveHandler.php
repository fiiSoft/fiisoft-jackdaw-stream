<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Sending\Dispatcher;

abstract class PrimitiveHandler implements Handler
{
    final public function prepare(): void
    {
        //noop
    }
    
    final public function dispatchFinished(): void
    {
        //noop
    }
}