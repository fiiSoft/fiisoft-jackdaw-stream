<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal;

use FiiSoft\Jackdaw\Stream;

trait StubMethods
{
    protected function prepareSubstream(): void
    {
        //noop
    }

    protected function continueIteration(bool $once = false): bool
    {
        //noop
        return false;
    }

    protected function sendTo(StreamPipe $stream): bool
    {
        //noop
        return false;
    }

    protected function processExternalPush(Stream $sender): bool
    {
        //noop
        return false;
    }

    protected function finish(): void
    {
        //noop
    }
}