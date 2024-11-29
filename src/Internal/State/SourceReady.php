<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal\State;

use FiiSoft\Jackdaw\Producer\Producer;

final class SourceReady extends Source
{
    /**
     * @param Producer<string|int, mixed> $producer
     */
    public function __construct(SourceData $data, Producer $producer, \Iterator $currentSource)
    {
        parent::__construct($data, $producer);
        
        $this->currentSource = $currentSource;
    }
    
    public function hasNextItem(): bool
    {
        if ($this->nextValue->isSet) {
            $this->item->key = $this->nextValue->key;
            $this->item->value = $this->nextValue->value;
            $this->nextValue->isSet = false;
            
            return true;
        }
        
        $this->currentSource->next();
        
        return $this->currentSource->valid();
    }
}