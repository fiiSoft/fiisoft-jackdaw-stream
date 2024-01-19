<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Sending;

use FiiSoft\Jackdaw\Internal\ForkCollaborator;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\ProcessOperation;

final class Feed extends ProcessOperation
{
    private ?ForkCollaborator $stream;
    
    public function __construct(ForkCollaborator $stream)
    {
        $this->stream = $stream;
    }
    
    public function handle(Signal $signal): void
    {
        if ($this->stream !== null && !$this->stream->process($signal)) {
            $this->stream = null;
        }
        
        $this->next->handle($signal);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        $signal = $this->createSignal();
        $item = $signal->item;
        
        foreach ($stream as $item->key => $item->value) {
            if ($this->stream !== null && !$this->stream->process($signal)) {
                $this->stream = null;
            }
            
            yield $item->key => $item->value;
        }
    }
    
    public function createFeedMany(Feed $next): FeedMany
    {
        return new FeedMany($this->stream, $next->stream);
    }
    
    public function stream(): ForkCollaborator
    {
        return $this->stream;
    }
    
    public function streamingFinished(Signal $signal): bool
    {
        return $this->next->streamingFinished($signal);
    }
}