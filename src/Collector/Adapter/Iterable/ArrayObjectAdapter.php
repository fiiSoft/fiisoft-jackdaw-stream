<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Collector\Adapter\Iterable;

use FiiSoft\Jackdaw\Stream;

final class ArrayObjectAdapter extends BaseIterableCollector
{
    private \ArrayObject $buffer;
    
    public function __construct(\ArrayObject $buffer, ?bool $allowKeys = true)
    {
        parent::__construct($allowKeys);
        
        $this->buffer = $buffer;
    }
    
    public function set($key, $value): void
    {
        $this->buffer[$key] = $value;
    }
    
    public function add($value): void
    {
        $this->buffer[] = $value;
    }
    
    public function toArray(): array
    {
        return $this->buffer->getArrayCopy();
    }
    
    public function clear(): void
    {
        $this->buffer->exchangeArray([]);
    }
    
    public function count(): int
    {
        return $this->buffer->count();
    }
    
    public function stream(): Stream
    {
        return Stream::from($this->buffer);
    }
    
    public function getIterator(): \ArrayObject
    {
        return $this->buffer;
    }
}