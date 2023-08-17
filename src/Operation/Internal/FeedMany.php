<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Internal;

use FiiSoft\Jackdaw\Internal\ForkCollaborator;
use FiiSoft\Jackdaw\Internal\Signal;

final class FeedMany extends StreamCollaborator
{
    /** @var ForkCollaborator [] */
    private array $streams;
    
    public function __construct(ForkCollaborator ...$streams)
    {
        if (empty($streams)) {
            throw new \InvalidArgumentException('FeedMany requires at least one stream');
        }
        
        $this->streams = $streams;
    }
    
    public function handle(Signal $signal): void
    {
        foreach ($this->streams as $key => $stream) {
            if (!$stream->process($signal)) {
                unset($this->streams[$key]);
            }
        }
        
        $this->next->handle($signal);
    }
    
    public function add(Feed $next): void
    {
        $this->streams[] = $next->stream();
    }
    
    public function streamingFinished(Signal $signal): bool
    {
        return $this->next->streamingFinished($signal);
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->streams = [];
            
            parent::destroy();
        }
    }
}