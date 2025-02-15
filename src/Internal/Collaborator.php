<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal;

use FiiSoft\Jackdaw\Operation\Operation;
use FiiSoft\Jackdaw\Producer\Producer;

abstract class Collaborator extends StreamPipe
{
    //*Signal->, *Stream->, *Source
    abstract protected function restartWith(Producer $producer, Operation $operation): void;
    
    //*Signal->, *Stream->, *Source
    abstract protected function continueWith(Producer $producer, Operation $operation): void;
    
    //*Signal->, *Stream->, *Source
    abstract protected function limitReached(Operation $operation): void;
    
    //*Signal->, *Stream->, *Source
    abstract protected function forget(Operation $operation): void;
    
    //*Signal->, *Stream->, *Source
    abstract protected function swapHead(Operation $operation): void;
    
    //*Signal->, *Stream->, *Source
    abstract protected function restoreHead(): void;
    
    //*Signal->, *Stream->, *Source
    abstract protected function setNextItem(Item $item): void;
}