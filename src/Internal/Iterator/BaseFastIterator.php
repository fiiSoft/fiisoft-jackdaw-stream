<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal\Iterator;

use FiiSoft\Jackdaw\Internal\StreamPipe;
use FiiSoft\Jackdaw\Stream;

/**
 * @implements \Iterator<string|int, mixed>
 */
abstract class BaseFastIterator extends StreamPipe implements \Iterator
{
    protected \Iterator $iterator;
    
    private Stream $stream;
    
    /**
     * @param iterable<string|int, mixed> $iterator
     */
    final public static function create(Stream $stream, iterable $iterator): self
    {
        if (\version_compare(\PHP_VERSION, '8.1.0') >= 0) {
            //@codeCoverageIgnoreStart
            return new FastIterator81($stream, $iterator);
            //@codeCoverageIgnoreEnd
        }
        
        return new FastIterator($stream, $iterator);
    }
    
    /**
     * @param iterable<string|int, mixed> $iterator
     */
    final protected function __construct(Stream $stream, iterable $iterator)
    {
        $this->stream = $stream;
        
        if ($iterator instanceof \Iterator) {
            $this->iterator = $iterator;
        } else {
            //@codeCoverageIgnoreStart
            $this->iterator = (static function () use ($iterator): \Generator {
                yield from $iterator;
            })();
            //@codeCoverageIgnoreEnd
        }
    }
    
    final public function valid(): bool
    {
        if ($this->iterator->valid()) {
            return true;
        }
        
        $this->stream->finish();
        
        return false;
    }
    
    final public function next(): void
    {
        $this->iterator->next();
    }
    
    final public function rewind(): void
    {
        $this->iterator->rewind();
    }
}