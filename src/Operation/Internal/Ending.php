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

    public function setNext(Operation $next, bool $direct = false): Operation
    {
        throw new \LogicException('It should never happen (Ending::setNext)');
    }

    public function setPrev(Operation $prev): void
    {
        //noop
    }

    public function removeFromChain(): Operation
    {
        throw new \LogicException('It should never happen (Ending::removeFromChain)');
    }

    public function streamingFinished(Signal $signal): bool
    {
        return false;
    }

    public function isLazy(): bool
    {
        return false;
    }
}