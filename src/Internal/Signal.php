<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal;

use FiiSoft\Jackdaw\Operation\Operation;
use FiiSoft\Jackdaw\Stream;

final class Signal extends Collaborator
{
    /** @var Item */
    public $item;
    
    /** @var bool */
    private $isStopped = false;
    
    /** @var bool */
    private $isInterrupted = false;
    
    /** @var bool */
    private $isEmpty = false;
    
    /** @var bool */
    private $isTerminated = false;
    
    /** @var bool */
    private $isRestarted = false;
    
    /** @var int */
    private $innerLoopLevel = 0;
    
    /** @var Stream */
    private $stream;
    
    public function __construct(Stream $stream)
    {
        $this->stream = $stream;
        $this->item = new Item();
    }
    
    public function stop()
    {
        $this->isStopped = true;
    }
    
    public function terminate()
    {
        $this->isStopped = true;
        $this->isTerminated = true;
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
    
    public function interrupt()
    {
        $this->isInterrupted = true;
    }
    
    public function isInterrupted(): bool
    {
        if ($this->isInterrupted) {
            $this->isInterrupted = false;
            return true;
        }
        
        return false;
    }
    
    /**
     * @param Operation $operation
     * @param Item[] $items
     */
    public function restartFrom(Operation $operation, array $items)
    {
        $this->isStopped = false;
        $this->isRestarted = true;
        $this->stream->restartFrom($operation, $items);
    }
    
    public function continueFrom(Operation $operation, array $items)
    {
        ++$this->innerLoopLevel;
        $this->stream->continueFrom($operation, $items);
    }
    
    public function streamIsEmpty()
    {
        $this->isStopped = true;
        $this->isEmpty = true;
        $this->stream->streamIsEmpty();
    }
    
    public function limitReached(Operation $operation)
    {
        $this->streamIsEmpty();
        $this->stream->limitReached($operation);
    }
    
    public function isStreamEmpty(): bool
    {
        return $this->isEmpty;
    }
    
    public function resume()
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
        //noop
        return false;
    }
}