<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal;

use FiiSoft\Jackdaw\Operation\Operation;
use FiiSoft\Jackdaw\Producer\Producer;

abstract class Collaborator extends StreamPipe
{
    //Signal, Stream, various operations
    abstract protected function restartWith(Producer $producer, Operation $operation): void;
    
    //Signal, Stream, various operations
    abstract protected function continueWith(Producer $producer, Operation $operation): void;
    
    //Signal, Stream, various operations
    abstract protected function limitReached(Operation $operation): void;
    
    //Signal, Stream, various operations
    abstract protected function forget(Operation $operation): void;
}