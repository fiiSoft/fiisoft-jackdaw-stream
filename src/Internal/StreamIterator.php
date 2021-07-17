<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal;

use FiiSoft\Jackdaw\Stream;

final class StreamIterator extends Collaborator implements \Iterator
{
    use StubMethods;
    
    private Stream $stream;
    private Item $item;
    
    private bool $isValid = false;
    
    public function __construct(Stream $stream)
    {
        $this->stream = $stream;
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
            //ok
        }
    }
    
    public function valid()
    {
        return $this->isValid;
    }
    
    public function rewind()
    {
        try {
            $this->stream->run();
        } catch (Interruption $e) {
            //ok
        }
    }
    
    public function setItem(Item $item): void
    {
        $this->item = $item;
        $this->isValid = true;
    }
}