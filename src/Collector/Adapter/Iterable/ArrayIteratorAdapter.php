<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Collector\Adapter\Iterable;

use FiiSoft\Jackdaw\Stream;

final class ArrayIteratorAdapter extends BaseIterableCollector
{
    /** @var \ArrayIterator<string|int, mixed> */
    private \ArrayIterator $iterator;
    
    /**
     * @param \ArrayIterator<string|int, mixed> $iterator
     */
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
    
    public function toArray(): array
    {
        return $this->iterator->getArrayCopy();
    }
    
    public function stream(): Stream
    {
        return Stream::from($this->iterator);
    }
    
    /**
     * @return \ArrayIterator<string|int, mixed>
     */
    public function getIterator(): \ArrayIterator
    {
        return $this->iterator;
    }
}