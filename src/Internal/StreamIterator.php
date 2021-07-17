<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal;

use FiiSoft\Jackdaw\Stream;

final class StreamIterator extends Collaborator implements \Iterator
{
    use StubMethods;
    
    /** @var Stream */
    private $stream;
    
    /** @var Item */
    private $item;
    
    /** @var bool */
    private $isValid = false;
    
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
    
    public function setItem(Item $item)
    {
        $this->item = $item;
        $this->isValid = true;
    }
}