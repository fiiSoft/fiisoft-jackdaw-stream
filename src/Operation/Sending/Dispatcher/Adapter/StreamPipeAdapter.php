<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Sending\Dispatcher\Adapter;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Internal\StreamPipe;
use FiiSoft\Jackdaw\Operation\Sending\Dispatcher\DispatchHandler;

final class StreamPipeAdapter extends StreamPipe implements DispatchHandler
{
    private ?StreamPipe $stream, $toFinish = null;
    
    private Signal $signal;
    
    public function __construct(StreamPipe $streamPipe)
    {
        $this->stream = $streamPipe;
        $this->signal = Signal::shared();
    }
    
    public function handle(Signal $signal): void
    {
        if ($this->stream !== null && !$this->stream->process($signal)) {
            $this->toFinish = $this->stream;
            $this->stream = null;
        }
    }
    
    /**
     * @inheritDoc
     */
    public function handlePair($value, $key): void
    {
        if ($this->stream !== null) {
            $this->signal->item->key = $key;
            $this->signal->item->value = $value;
            
            if (!$this->stream->process($this->signal)) {
                $this->toFinish = $this->stream;
                $this->stream = null;
            }
        }
    }
    
    public function prepare(): void
    {
        $this->stream->prepareSubstream(false);
    }
    
    public function dispatchFinished(): void
    {
        $stream = $this->stream ?? $this->toFinish;
        $this->stream = $this->toFinish = null;
        
        if ($stream !== null) {
            $stream->continueIteration();
        }
    }
}