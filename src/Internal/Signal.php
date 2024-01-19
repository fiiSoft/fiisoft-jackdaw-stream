<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal;

use FiiSoft\Jackdaw\Operation\Operation;
use FiiSoft\Jackdaw\Producer\Producer;
use FiiSoft\Jackdaw\Stream;

final class Signal extends Collaborator
{
    public Item $item;
    
    public bool $isWorking = true;
    public bool $isEmpty = false;
    public bool $isError = false;
    
    private Stream $stream;
    
    public function __construct(Stream $stream)
    {
        $this->stream = $stream;
        $this->item = new Item();
    }
    
    public function stop(): void
    {
        $this->isWorking = false;
    }
    
    public function abort(): void
    {
        $this->isError = true;
        $this->isWorking = false;
    }
    
    public function restartWith(Producer $producer, Operation $operation): void
    {
        $this->resume();
        $this->stream->restartWith($producer, $operation);
    }
    
    public function continueWith(Producer $producer, Operation $operation): void
    {
        $this->stream->continueWith($producer, $operation);
    }
    
    public function forget(Operation $operation): void
    {
        $this->stream->forget($operation);
    }
    
    public function limitReached(Operation $operation): void
    {
        $this->streamIsEmpty();
        $this->stream->limitReached($operation);
    }
    
    public function streamIsEmpty(): void
    {
        $this->isWorking = false;
        $this->isEmpty = true;
    }
    
    public function resume(): void
    {
        $this->isWorking = true;
        $this->isEmpty = false;
    }
}