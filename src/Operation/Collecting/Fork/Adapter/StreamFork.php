<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Collecting\Fork\Adapter;

use FiiSoft\Jackdaw\Internal\Destroyable;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Internal\StreamPipe;
use FiiSoft\Jackdaw\Operation\Collecting\Fork\ForkHandler;

final class StreamFork extends StreamPipe implements ForkHandler
{
    private StreamPipe $stream;
    private Item $item;
    private Signal $signal;
    
    public function __construct(StreamPipe $stream, bool $prepare = false)
    {
        $this->stream = $stream;
        
        if ($prepare) {
            $this->prepare();
        }
    }
    
    public function create(): ForkHandler
    {
        return new self($this->stream->cloneForFork(), true);
    }
    
    public function prepare(): void
    {
        $this->signal = Signal::shared();
        $this->item = $this->signal->item;
    }
    
    /**
     * @inheritDoc
     */
    public function accept($value, $key): void
    {
        $this->item->value = $value;
        $this->item->key = $key;
        
        $this->stream->process($this->signal);
    }
    
    public function isEmpty(): bool
    {
        $lastOperation = $this->stream->getLastOperation();
        
        return $lastOperation === null || $lastOperation->notFound();
    }
    
    /**
     * @inheritDoc
     */
    public function result()
    {
        $lastOperation = $this->stream->getLastOperation();
        
        return $lastOperation !== null ? $lastOperation->get() : null;
    }
    
    public function destroy(): void
    {
        if ($this->stream instanceof Destroyable) {
            $this->stream->destroy();
        }
    }
}