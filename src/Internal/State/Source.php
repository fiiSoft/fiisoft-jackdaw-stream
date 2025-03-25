<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal\State;

use FiiSoft\Jackdaw\Internal\Destroyable;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Pipe;
use FiiSoft\Jackdaw\Operation\Operation;
use FiiSoft\Jackdaw\Producer\MultiProducer;
use FiiSoft\Jackdaw\Producer\Producer;
use FiiSoft\Jackdaw\Producer\ProducerReady;
use FiiSoft\Jackdaw\Producer\Producers;

abstract class Source extends StreamSource implements Destroyable
{
    /** @var Producer<string|int, mixed> */
    public Producer $producer;
    
    protected SourceData $data;
    protected \Iterator $currentSource;
    protected Item $item;
    
    protected bool $hasNextValue = false;
    
    private Sources $sources;
    private Pipe $pipe;
    
    private bool $isDestroying = false;
    
    /**
     * @param Producer<string|int, mixed> $producer
     */
    public function __construct(SourceData $data, Producer $producer)
    {
        $this->producer = $producer;
        
        $this->data = $data;
        $this->item = $data->signal->item;
        $this->sources = $data->sources;
        $this->pipe = $data->pipe;
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
    
    final protected function restartWith(Producer $producer, Operation $operation): void
    {
        $this->pipe->head = $operation;
        $this->sourceIsNotReady($producer);
    }
    
    final protected function continueWith(Producer $producer, Operation $operation): void
    {
        $this->sources->stack[] = $this;
        
        $this->pipe->stack[] = $this->pipe->head;
        $this->pipe->head = $operation;
        
        $this->sourceIsNotReady($producer);
    }
    
    /**
     * @return bool true when stack is empty
     */
    final public function restoreFromStack(): bool
    {
        $this->pipe->head = \array_pop($this->pipe->stack);
        
        if (!empty($this->sources->stack)) {
            $this->data->stream->setSource(\array_pop($this->sources->stack));
        }
        
        return empty($this->sources->stack);
    }
    
    final protected function swapHead(Operation $operation): void
    {
        $this->pipe->heads[] = $this->pipe->head;
        $this->pipe->head = $operation;
    }
    
    final protected function restoreHead(): void
    {
        $this->pipe->head = \array_pop($this->pipe->heads);
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
        
        $this->sourceIsNotReady(MultiProducer::oneTime($this->producer));
    }
    
    /**
     * @param Producer<string|int, mixed> $producer
     */
    private function sourceIsNotReady(Producer $producer): void
    {
        $this->data->stream->setSource(new SourceNotReady($this->data, $producer));
    }
    
    final protected function setNextItem(Item $item): void
    {
        $this->item->key = $item->key;
        $this->item->value = $item->value;
        $this->hasNextValue = true;
    }
    
    abstract public function hasNextItem(): bool;
    
    final public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->isDestroying = true;
            
            $this->producer->destroy();
            $this->sources->destroy();
        }
    }
}