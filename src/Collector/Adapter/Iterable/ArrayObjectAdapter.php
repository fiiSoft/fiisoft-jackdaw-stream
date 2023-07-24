<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Collector\Adapter\Iterable;

use FiiSoft\Jackdaw\Collector\BaseCollector;
use FiiSoft\Jackdaw\Collector\IterableCollector;
use FiiSoft\Jackdaw\Stream;

final class ArrayObjectAdapter extends BaseCollector implements IterableCollector, \IteratorAggregate
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
    
    public function getData(): array
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
    
    final public function toJson(int $flags = 0): string
    {
        return \json_encode($this->getData(), \JSON_THROW_ON_ERROR | $flags);
    }
    
    final public function toString(string $separator = ','): string
    {
        return \implode($separator, $this->getData());
    }
    
    public function getIterator(): \ArrayObject
    {
        return $this->buffer;
    }
}