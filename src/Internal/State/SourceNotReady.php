<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal\State;

use FiiSoft\Jackdaw\Internal\Item;

final class SourceNotReady extends Source
{
    public function hasNextItem(): bool
    {
        try {
            $this->initializeSource();
            
            if ($this->isLoop) {
                $this->currentSource->send($this->signal->item);
            }
            
            return $this->currentSource->valid();
        } finally {
            $this->sourceIsReady();
        }
    }
    
    public function setNextValue(Item $item): void
    {
        $this->initializeSource();
        
        $this->currentSource->send($item);
        
        $this->sourceIsReady();
    }
    
    private function sourceIsReady(): void
    {
        $this->stream->setSource(new SourceReady(
            $this->isLoop,
            $this->stream,
            $this->producer,
            $this->signal,
            $this->pipe,
            $this->stack,
            $this->currentSource
        ));
    }
}