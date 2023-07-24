<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Collector\Adapter\Iterable;

use FiiSoft\Jackdaw\Collector\BaseCollector;
use FiiSoft\Jackdaw\Collector\IterableCollector;
use FiiSoft\Jackdaw\Stream;

final class ArrayIteratorAdapter extends BaseCollector implements IterableCollector, \IteratorAggregate
{
    private \ArrayIterator $iterator;
    
    public function __construct(\ArrayIterator $iterator, ?bool $allowKeys = true)
    {
        parent::__construct($allowKeys);
        
        $this->iterator = $iterator;
    }
    
    /**
     * @inheritDoc
     */
    public function set($key, $value): void
    {
        $this->iterator[$key] = $value;
    }
    
    /**
     * @inheritDoc
     */
    public function add($value): void
    {
        $this->iterator[] = $value;
    }
    
    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return $this->iterator->count();
    }
    
    public function clear(): void
    {
        $keys = \array_keys($this->iterator->getArrayCopy());
        foreach ($keys as $key) {
            unset($this->iterator[$key]);
        }
    }
    
    public function getData(): array
    {
        return $this->iterator->getArrayCopy();
    }
    
    public function stream(): Stream
    {
        return Stream::from($this->iterator);
    }
    
    public function toString(string $separator = ','): string
    {
        return \implode($separator, $this->getData());
    }
    
    public function toJson(int $flags = 0): string
    {
        return \json_encode($this->getData(), \JSON_THROW_ON_ERROR | $flags);
    }
    
    public function getIterator(): \ArrayIterator
    {
        return $this->iterator;
    }
}