<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Collector;

use FiiSoft\Jackdaw\Stream;

final class DefaultCollector extends BaseCollector implements IterableCollector, \IteratorAggregate
{
    private array $buffer;
    
    public function __construct(array $buffer = [], ?bool $allowKeys = true)
    {
        parent::__construct($allowKeys);
        
        $this->buffer = $buffer;
    }
    
    /**
     * @inheritDoc
     */
    public function set($key, $value): void
    {
        $this->buffer[$key] = $value;
    }
    
    /**
     * @inheritDoc
     */
    public function add($value): void
    {
        $this->buffer[] = $value;
    }
    
    public function getData(): array
    {
        return $this->buffer;
    }
    
    public function clear(): void
    {
        $this->buffer = [];
    }
    
    public function count(): int
    {
        return \count($this->buffer);
    }
    
    public function stream(): Stream
    {
        return Stream::from($this->buffer);
    }
    
    final public function toJson(int $flags = 0): string
    {
        return \json_encode($this->buffer, \JSON_THROW_ON_ERROR | $flags);
    }
    
    final public function toString(string $separator = ','): string
    {
        return \implode($separator, $this->buffer);
    }
    
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->buffer);
    }
}