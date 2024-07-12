<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Sending;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Internal\ForkCollaborator;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Internal\SourceAware;
use FiiSoft\Jackdaw\Operation\Internal\ProcessOperation;
use FiiSoft\Jackdaw\Stream;

final class FeedMany extends ProcessOperation
{
    /** @var ForkCollaborator [] */
    private array $streams;
    
    public function __construct(ForkCollaborator ...$streams)
    {
        if (empty($streams)) {
            throw InvalidParamException::byName('streams');
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
    
    public function buildStream(iterable $stream): iterable
    {
        $signal = $this->createSignal();
        $item = $signal->item;
        
        foreach ($stream as $item->key => $item->value) {
            foreach ($this->streams as $key => $sub) {
                if (!$sub->process($signal)) {
                    unset($this->streams[$key]);
                }
            }
            
            yield $item->key => $item->value;
        }
    }
    
    public function add(Feed $next): void
    {
        $this->streams[] = $next->stream();
    }
    
    public function streamingFinished(Signal $signal): bool
    {
        return $this->next->streamingFinished($signal);
    }
    
    public function assignStream(Stream $stream): void
    {
        parent::assignStream($stream);
        
        foreach ($this->streams as $collaborator) {
            if ($collaborator instanceof SourceAware) {
                $collaborator->assignSource($stream);
            } elseif ($collaborator instanceof ForkCollaborator) {
                $collaborator->assignParent($stream);
            }
        }
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->streams = [];
            
            parent::destroy();
        }
    }
    
    public function resume(): void
    {
        foreach ($this->streams as $stream) {
            $stream->resume();
        }
        
        parent::resume();
    }
}