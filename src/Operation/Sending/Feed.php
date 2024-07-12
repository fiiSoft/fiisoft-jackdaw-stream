<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Sending;

use FiiSoft\Jackdaw\Internal\ForkCollaborator;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Internal\SourceAware;
use FiiSoft\Jackdaw\Operation\Internal\ProcessOperation;
use FiiSoft\Jackdaw\Stream;

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
    
    public function assignStream(Stream $stream): void
    {
        parent::assignStream($stream);
        
        if ($this->stream instanceof SourceAware) {
            $this->stream->assignSource($stream);
        } elseif ($this->stream instanceof ForkCollaborator) {
            $this->stream->assignParent($stream);
        }
    }
    
    public function resume(): void
    {
        if ($this->stream !== null) {
            $this->stream->resume();
        }
        
        parent::resume();
    }
}