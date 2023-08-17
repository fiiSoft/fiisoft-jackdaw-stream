<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Internal\Dispatcher;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Internal\StreamPipe;

final class StreamPipeAdapter extends StreamPipe implements Handler
{
    private ?StreamPipe $stream;
    
    public function __construct(StreamPipe $streamPipe)
    {
        $this->stream = $streamPipe;
    }
    
    public function handle(Signal $signal): void
    {
        if ($this->stream !== null && !$this->stream->process($signal)) {
            $this->stream = null;
        }
    }
}