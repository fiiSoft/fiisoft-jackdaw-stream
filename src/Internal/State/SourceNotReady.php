<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal\State;

use FiiSoft\Jackdaw\Internal\Item;

final class SourceNotReady extends Source
{
    public function hasNextItem(): bool
    {
        if ($this->nextValue->isSet) {
            $this->item->key = $this->nextValue->key;
            $this->item->value = $this->nextValue->value;
            $this->nextValue->isSet = false;
            
            return true;
        }
        
        $this->initializeSource();
        
        $isValid = $this->currentSource->valid();
        $this->sourceIsReady();
        
        return $isValid;
    }
    
    public function setNextItem(Item $item): void
    {
        $this->nextValue->isSet = true;
        $this->nextValue->key = $item->key;
        $this->nextValue->value = $item->value;
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
            $this->currentSource,
            $this->nextValue
        ));
    }
}