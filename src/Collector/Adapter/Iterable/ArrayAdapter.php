<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Collector\Adapter\Iterable;

use FiiSoft\Jackdaw\Stream;

final class ArrayAdapter extends BaseIterableCollector
{
    private array $storage;
    
    public function __construct(array &$storage, bool $allowsKeys = true)
    {
        parent::__construct($allowsKeys);
        
        $this->storage = &$storage;
    }
    
    public function set($key, $value): void
    {
        $this->storage[$key] = $value;
    }
    
    public function add($value): void
    {
        $this->storage[] = $value;
    }
    
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->storage);
    }
    
    public function count(): int
    {
        return \count($this->storage);
    }
    
    public function clear(): void
    {
        $this->storage = [];
    }
    
    public function getData(): array
    {
        return $this->storage;
    }
    
    public function stream(): Stream
    {
        return Stream::from($this->storage);
    }
}