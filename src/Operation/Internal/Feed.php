<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Internal;

use FiiSoft\Jackdaw\Internal\ForkCollaborator;
use FiiSoft\Jackdaw\Internal\Signal;

final class Feed extends StreamCollaborator
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