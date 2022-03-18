<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Internal;

use FiiSoft\Jackdaw\Internal\StreamPipe;
use FiiSoft\Jackdaw\Internal\Signal;

final class Feed extends BaseOperation
{
    private ?StreamPipe $stream;
    
    public function __construct(StreamPipe $stream)
    {
        $this->stream = $stream;
    }
    
    public function handle(Signal $signal): void
    {
        if ($this->stream !== null && !$signal->sendTo($this->stream)) {
            $this->stream = null;
        }
        
        $this->next->handle($signal);
    }
}