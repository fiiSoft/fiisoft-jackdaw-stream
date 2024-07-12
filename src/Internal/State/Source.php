<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal\State;

use FiiSoft\Jackdaw\Internal\Destroyable;
use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Pipe;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Operation;
use FiiSoft\Jackdaw\Producer\MultiProducer;
use FiiSoft\Jackdaw\Producer\Producer;
use FiiSoft\Jackdaw\Producer\ProducerReady;
use FiiSoft\Jackdaw\Producer\Producers;
use FiiSoft\Jackdaw\Stream;

abstract class Source extends StreamSource implements Destroyable
{
    /** @var Producer<string|int, mixed> */
    public Producer $producer;
    
    protected \Iterator $currentSource;
    protected NextValue $nextValue;
    protected Stream $stream;
    protected Signal $signal;
    protected Stack $stack;
    protected Pipe $pipe;
    protected Item $item;
    
    protected bool $isLoop;
    
    private bool $isDestroying = false;
    
    /**
     * @param Producer<string|int, mixed> $producer
     */
    public function __construct(
        bool $isLoop,
        Stream $stream,
        Producer $producer,
        Signal $signal,
        Pipe $pipe,
        Stack $stack,
        ?NextValue $nextValue = null
    ) {
        $this->producer = $producer;
        $this->isLoop = $isLoop;
        $this->stream = $stream;
        $this->signal = $signal;
        $this->stack = $stack;
        $this->pipe = $pipe;
        $this->nextValue = $nextValue ?? new NextValue();
        
        $this->item = $this->signal->item;
    }
    
    /**
     * @param array<ProducerReady|resource|callable|iterable<string|int, mixed>|string> $producers
     */
    final public function addProducers(array $producers): void
    {
        if ($this->producer instanceof MultiProducer) {
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
        $this->currentSource = Helper::createItemProducer($this->item, $this->producer);
    }
    
    /**
     * @inheritDoc
     */
    final protected function restartWith(Producer $producer, Operation $operation): void
    {
        $this->pipe->head = $operation;
        $this->sourceIsNotReady($producer);
    }
    
    /**
     * @inheritDoc
     */
    final protected function continueWith(Producer $producer, Operation $operation): void
    {
        $this->stack->states[] = $this;
        
        $this->pipe->stack[] = $this->pipe->head;
        $this->pipe->head = $operation;
        
        $this->sourceIsNotReady($producer);
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
        if ($this->producer instanceof MultiProducer && $this->producer->isOneTime()) {
            return;
        }
        
        if (!$isLoop) {
            $this->pipe->prepare();
        }
        
        $this->isLoop = $isLoop;
        $this->sourceIsNotReady(MultiProducer::oneTime($this->producer));
    }
    
    /**
     * @param Producer<string|int, mixed> $producer
     */
    private function sourceIsNotReady(Producer $producer): void
    {
        $this->stream->setSource(new SourceNotReady(
            $this->isLoop, $this->stream, $producer, $this->signal, $this->pipe, $this->stack
        ));
    }
    
    abstract public function hasNextItem(): bool;
    
    abstract public function setNextItem(Item $item): void;
    
    final public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->isDestroying = true;
            
            $this->producer->destroy();
            $this->stack->destroy();
        }
    }
}