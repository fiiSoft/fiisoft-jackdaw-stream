<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal;

use FiiSoft\Jackdaw\Operation\Operation;
use FiiSoft\Jackdaw\Stream;

trait StubMethods
{
    protected function restartFrom(Operation $operation, array $items)
    {
        //noop
    }
    
    protected function continueFrom(Operation $operation, array $items)
    {
        //noop
    }
    
    protected function limitReached(Operation $operation)
    {
        //noop
    }
    
    protected function continueIteration(bool $once = false): bool
    {
        //noop
        return false;
    }
    
    protected function streamIsEmpty()
    {
        //noop
    }
    
    protected function sendTo(BaseStreamPipe $stream): bool
    {
        //noop
        return false;
    }
    
    protected function processExternalPush(Stream $sender): bool
    {
        //noop
        return false;
    }
}