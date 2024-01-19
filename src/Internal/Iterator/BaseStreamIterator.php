<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal\Iterator;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\StreamPipe;
use FiiSoft\Jackdaw\Stream;

abstract class BaseStreamIterator extends StreamPipe implements \Iterator
{
    protected Item $item;
    
    private Stream $stream;
    private bool $isValid = false;
    
    final public static function create(Stream $stream, Item $item): self
    {
        if (\version_compare(\PHP_VERSION, '8.1.0') >= 0) {
            //@codeCoverageIgnoreStart
            return new StreamIterator81($stream, $item);
            //@codeCoverageIgnoreEnd
        }
        
        return new StreamIterator($stream, $item);
    }
    
    final protected function __construct(Stream $stream, Item $item)
    {
        $this->stream = $stream;
        $this->item = $item;
    }
    
    final public function next(): void
    {
        $this->isValid = false;
        try {
            $this->stream->continueIteration();
        } catch (Interruption $e) {
            $this->isValid = true;
        }
    }
    
    final public function valid(): bool
    {
        if ($this->isValid) {
            return true;
        }
        
        $this->stream->finish();
        
        return false;
    }
    
    final public function rewind(): void
    {
        $this->isValid = false;
        try {
            $this->stream->run();
        } catch (Interruption $e) {
            $this->isValid = true;
        }
    }
}