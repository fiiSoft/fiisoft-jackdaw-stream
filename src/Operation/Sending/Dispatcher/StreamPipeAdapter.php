<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Sending\Dispatcher;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Internal\StreamPipe;
use FiiSoft\Jackdaw\Stream;

final class StreamPipeAdapter extends StreamPipe implements Handler
{
    private ?StreamPipe $stream;
    
    private Signal $signal;
    
    public function __construct(StreamPipe $streamPipe)
    {
        $this->stream = $streamPipe;
        $this->signal = new Signal(Stream::empty());
    }
    
    public function handle(Signal $signal): void
    {
        if ($this->stream !== null && !$this->stream->process($signal)) {
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
                $this->stream = null;
            }
        }
    }
}