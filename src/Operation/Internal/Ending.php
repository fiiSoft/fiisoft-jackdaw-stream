<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Internal;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Operation;

final class Ending implements Operation
{
    public function handle(Signal $signal): void
    {
        //noop
    }
    
    public function setNext(Operation $next): Operation
    {
        throw new \LogicException('It should never happen (Ending::setNext)');
    }
    
    public function streamingFinished(Signal $signal): void
    {
        //noop
    }
    
    public function isLazy(): bool
    {
        return false;
    }
}