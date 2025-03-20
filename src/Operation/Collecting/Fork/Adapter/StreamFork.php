<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Collecting\Fork\Adapter;

use FiiSoft\Jackdaw\Internal\Destroyable;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Internal\StreamPipe;
use FiiSoft\Jackdaw\Operation\Collecting\Fork\ForkHandler;
use FiiSoft\Jackdaw\Operation\LastOperation;
use FiiSoft\Jackdaw\Stream;

final class StreamFork extends StreamPipe implements ForkHandler
{
    private StreamPipe $stream;
    
    private Signal $signal;
    private Item $item;
    
    public function __construct(StreamPipe $stream, ?Signal $signal = null, bool $isHandler = true)
    {
        if ($isHandler) {
            $stream->prepareSubstream(false);
        }
        
        $this->stream = $isHandler && $stream instanceof LastOperation
            ? $stream->getStream()
            : $stream;
        
        $this->signal = $signal ?? new Signal(Stream::empty());
        $this->item = $this->signal->item;
    }
    
    public function create(): ForkHandler
    {
        return new self($this->stream->cloneStream(), $this->signal, false);
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