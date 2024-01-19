<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Mapping\ChunkBy;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Mapping\ChunkBy;

final class ChunkByKeepKeys extends ChunkBy
{
    public function handle(Signal $signal): void
    {
        $item = $signal->item;
        
        $classifier = $this->discriminator->classify($item->value, $item->key);
        
        if ($this->previous === $classifier) {
            $this->chunked[$item->key] = $item->value;
        } elseif ($this->previous === null) {
            $this->previous = $classifier;
            $this->chunked[$item->key] = $item->value;
        } else {
            $chunked = $this->chunked;
            $this->chunked = [$item->key => $item->value];
            
            $item->value = $chunked;
            $item->key = $this->previous;
            $this->previous = $classifier;
            
            $this->next->handle($signal);
        }
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            $classifier = $this->discriminator->classify($value, $key);
            
            if ($this->previous === $classifier) {
                $this->chunked[$key] = $value;
            } elseif ($this->previous === null) {
                $this->previous = $classifier;
                $this->chunked[$key] = $value;
            } else {
                yield $this->previous => $this->chunked;
                
                $this->chunked = [$key => $value];
                $this->previous = $classifier;
            }
        }
        
        if (!empty($this->chunked)) {
            yield $this->previous => $this->chunked;
            
            $this->chunked = [];
            $this->previous = null;
        }
    }
}