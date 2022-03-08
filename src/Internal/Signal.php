<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal;

use FiiSoft\Jackdaw\Operation\Operation;
use FiiSoft\Jackdaw\Stream;

final class Signal extends Collaborator
{
    public Item $item;
    
    private bool $isStopped = false;
    private bool $isEmpty = false;
    private bool $isError = false;
    private bool $isTerminated = false;
    private bool $isRestarted = false;
    private int $innerLoopLevel = 0;
    
    private Stream $stream;
    
    public function __construct(Stream $stream)
    {
        $this->stream = $stream;
        $this->item = new Item();
    }
    
    public function stop(): void
    {
        $this->isStopped = true;
    }
    
    public function abort(): void
    {
        $this->isError = true;
        $this->terminate();
    }
    
    public function terminate(): void
    {
        $this->isStopped = true;
        $this->isTerminated = true;
    }
    
    public function isError(): bool
    {
        return $this->isError;
    }
    
    public function isFinished(): bool
    {
        if ($this->isTerminated) {
            return true;
        }
    
        if ($this->innerLoopLevel > 0) {
            --$this->innerLoopLevel;
            return true;
        }
        
        return $this->isRestarted;
    }
    
    public function isStopped(): bool
    {
        return $this->isStopped && $this->innerLoopLevel === 0;
    }
    
    /**
     * @param Operation $operation
     * @param Item[] $items
     */
    public function restartFrom(Operation $operation, array $items): void
    {
        $this->isStopped = false;
        $this->isRestarted = true;
        $this->stream->restartFrom($operation, $items);
    }
    
    public function continueFrom(Operation $operation, array $items): void
    {
        ++$this->innerLoopLevel;
        $this->stream->continueFrom($operation, $items);
    }
    
    public function streamIsEmpty(): void
    {
        $this->isStopped = true;
        $this->isEmpty = true;
        $this->stream->streamIsEmpty();
    }
    
    public function limitReached(Operation $operation): void
    {
        $this->streamIsEmpty();
        $this->stream->limitReached($operation);
    }
    
    public function isStreamEmpty(): bool
    {
        return $this->isEmpty;
    }
    
    public function resume(): void
    {
        $this->isStopped = false;
        $this->isEmpty = false;
    }
    
    protected function continueIteration(bool $once = false): bool
    {
        return false;
    }
    
    public function sendTo(BaseStreamPipe $stream): bool
    {
        return $this->stream->sendTo($stream);
    }
    
    protected function processExternalPush(Stream $sender): bool
    {
        return false;
    }
    
    protected function finish(): void
    {
        //noop
    }
}