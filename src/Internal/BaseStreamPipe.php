<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal;

use FiiSoft\Jackdaw\Stream;

abstract class BaseStreamPipe implements StreamPipe
{
    abstract protected function sendTo(BaseStreamPipe $stream): bool;
    
    abstract protected function processExternalPush(Stream $sender): bool;
    
    abstract protected function continueIteration(bool $once = false): bool;
}