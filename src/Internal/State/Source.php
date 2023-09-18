<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal\State;

use FiiSoft\Jackdaw\Internal\Collaborator;
use FiiSoft\Jackdaw\Internal\Destroyable;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Pipe;
use FiiSoft\Jackdaw\Internal\ResultCaster;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Operation;
use FiiSoft\Jackdaw\Producer\Internal\PushProducer;
use FiiSoft\Jackdaw\Producer\Tech\MultiSourcedProducer;
use FiiSoft\Jackdaw\Producer\Producer;
use FiiSoft\Jackdaw\Producer\Producers;
use FiiSoft\Jackdaw\Stream;

abstract class Source extends Collaborator implements Destroyable
{
    protected \Generator $currentSource;
    protected Producer $producer;
    protected Stream $stream;
    protected Signal $signal;
    protected Stack $stack;
    protected Pipe $pipe;
    
    protected bool $isLoop;
    private bool $isDestroying = false;
    
    public function __construct(
        bool $isLoop,
        Stream $stream,
        Producer $producer,
        Signal $signal,
        Pipe $pipe,
        Stack $stack
    ) {
        $this->producer = $producer;
        $this->isLoop = $isLoop;
        $this->stream = $stream;
        $this->signal = $signal;
        $this->stack = $stack;
        $this->pipe = $pipe;
    }
    
    /**
     * @param array<Stream|Producer|ResultCaster|\Iterator|\PDOStatement|callable|resource|array> $producers
     */
    final public function addProducers(array $producers): void
    {
        if ($this->producer instanceof MultiSourcedProducer) {
            foreach ($producers as $producer) {
                $this->producer->addProducer(Producers::getAdapter($producer));
            }
        } else {
            $this->sourceIsNotReady(Producers::multiSourced($this->producer, ...$producers));
        }
    }
    
    /**
     * @return bool true when stack is empty
     */
    final public function restoreFromStack(): bool
    {
        $this->pipe->head = \array_pop($this->pipe->stack);
        
        if (!empty($this->stack->states)) {
            $this->stream->setSource(\array_pop($this->stack->states));
        }
        
        return empty($this->stack->states);
    }
    
    final protected function initializeSource(): void
    {
        $this->currentSource = $this->producer->feed($this->signal->item);
    }
    
    final protected function restartWith(Producer $producer, Operation $operation): void
    {
        $this->pipe->head = $operation;
        $this->sourceIsNotReady($producer);
    }
    
    final protected function continueWith(Producer $producer, Operation $operation): void
    {
        $this->stack->states[] = $this;
        
        $this->pipe->stack[] = $this->pipe->head;
        $this->pipe->head = $operation;
        
        $this->sourceIsNotReady($producer);
    }
    
    final protected function continueFrom(Operation $operation): void
    {
        $this->pipe->head = $operation;
    }
    
    final protected function forget(Operation $operation): void
    {
        $current = $this->pipe->head;
        
        while ($current !== $operation && $current !== null) {
            $current = $current->getNext();
        }
        
        if ($current !== null) {
            $current->removeFromChain();
        }
        
        if ($this->pipe->head === $operation) {
            $this->pipe->head = $operation->getNext();
        }
        
        foreach ($this->pipe->stack as $key => $stacked) {
            if ($stacked === $operation) {
                unset($this->pipe->stack[$key]);
                break;
            }
        }
    }
    
    final protected function limitReached(Operation $operation): void
    {
        $this->pipe->head = $operation;
        $this->pipe->stack = [];
    }
    
    final protected function prepareSubstream(bool $isLoop): void
    {
        if (! $this->producer instanceof PushProducer) {
            if (!$isLoop) {
                $this->pipe->prepare();
            }
            
            $this->isLoop = $isLoop;
            $this->sourceIsNotReady(new PushProducer($isLoop, $this->producer));
        }
    }
    
    abstract public function hasNextItem(): bool;
    
    abstract public function setNextValue(Item $item): void;
    
    private function sourceIsNotReady(Producer $producer): void
    {
        $this->stream->setSource(new SourceNotReady(
            $this->isLoop, $this->stream, $producer, $this->signal, $this->pipe, $this->stack
        ));
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->isDestroying = true;
            
            $this->producer->destroy();
            $this->stack->destroy();
        }
    }
}