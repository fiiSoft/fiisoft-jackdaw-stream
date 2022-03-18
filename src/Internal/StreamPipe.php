<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal;

use FiiSoft\Jackdaw\Stream;

abstract class StreamPipe
{
    abstract protected function prepareSubstream(): void;
    
    abstract protected function sendTo(StreamPipe $stream): bool;
    
    abstract protected function processExternalPush(Stream $sender): bool;
    
    abstract protected function continueIteration(bool $once = false): bool;
    
    abstract protected function finish(): void;
}