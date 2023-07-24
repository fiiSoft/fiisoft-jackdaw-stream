<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal;

use FiiSoft\Jackdaw\Stream;

final class StreamIterator extends StreamPipe implements \Iterator
{
    private Stream $stream;
    private Item $item;
    
    private bool $isValid = false;
    
    public function __construct(Stream $stream, Item $item)
    {
        $this->stream = $stream;
        $this->item = $item;
    }
    
    public function current()
    {
        return $this->item->value;
    }
    
    public function key()
    {
        return $this->item->key;
    }
    
    public function next()
    {
        $this->isValid = false;
        try {
            $this->stream->continueIteration();
        } catch (Interruption $e) {
            $this->isValid = true;
        }
    }
    
    public function valid()
    {
        if ($this->isValid) {
            return true;
        }
        
        $this->stream->finish();
        
        return false;
    }
    
    public function rewind()
    {
        $this->isValid = false;
        try {
            $this->stream->run();
        } catch (Interruption $e) {
            $this->isValid = true;
        }
    }
}