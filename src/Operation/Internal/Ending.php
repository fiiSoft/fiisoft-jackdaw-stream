<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Internal;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Operation;

final class Ending implements Operation
{
    public function handle(Signal $signal)
    {
        //noop
    }

    public function setNext(Operation $next, bool $direct = false): Operation
    {
        throw new \LogicException('It should never happen (Ending::setNext)');
    }

    public function setPrev(Operation $prev)
    {
        //noop
    }

    public function removeFromChain(): Operation
    {
        throw new \LogicException('It should never happen (Ending::removeFromChain)');
    }

    public function streamingFinished(Signal $signal)
    {
        //noop
    }

    public function isLazy(): bool
    {
        return false;
    }
}