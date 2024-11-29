<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal\State;

use FiiSoft\Jackdaw\Internal\Helper;

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
        
        $this->currentSource = Helper::createItemProducer($this->item, $this->producer);
        
        if ($this->currentSource->valid()) {
            $this->sourceIsReady();
            
            return true;
        }
        
        return false;
    }
    
    private function sourceIsReady(): void
    {
        $this->stream->setSource(new SourceReady(
            $this->data,
            $this->producer,
            $this->currentSource
        ));
    }
}