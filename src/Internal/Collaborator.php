<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal;

use FiiSoft\Jackdaw\Operation\Operation;
use FiiSoft\Jackdaw\Producer\Producer;

abstract class Collaborator extends StreamPipe
{
    abstract protected function restartWith(Producer $producer, Operation $operation): void;
    
    abstract protected function continueWith(Producer $producer, Operation $operation): void;
    
    abstract protected function limitReached(Operation $operation): void;
}