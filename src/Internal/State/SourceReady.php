<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal\State;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Pipe;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Producer\Producer;
use FiiSoft\Jackdaw\Stream;

final class SourceReady extends Source
{
    public function __construct(
        bool $isLoop,
        Stream $stream,
        Producer $producer,
        Signal $signal,
        Pipe $pipe,
        Stack $stack,
        \Iterator $currentSource,
        ?NextValue $nextValue = null
    ) {
        parent::__construct($isLoop, $stream, $producer, $signal, $pipe, $stack, $nextValue);
        
        $this->currentSource = $currentSource;
    }
    
    public function setNextItem(Item $item): void
    {
        $this->nextValue->isSet = true;
        $this->nextValue->key = $item->key;
        $this->nextValue->value = $item->value;
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