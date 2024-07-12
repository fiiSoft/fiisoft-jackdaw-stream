<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Collector;

use FiiSoft\Jackdaw\Collector\Adapter\Iterable\BaseIterableCollector;
use FiiSoft\Jackdaw\Stream;

final class DefaultCollector extends BaseIterableCollector
{
    /** @var array<string|int, mixed> */
    private array $buffer;
    
    /**
     * @param array<string|int, mixed> $buffer
     */
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
    
    public function toArray(): array
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
    
    /**
     * @return \ArrayIterator<string|int, mixed>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->buffer);
    }
}