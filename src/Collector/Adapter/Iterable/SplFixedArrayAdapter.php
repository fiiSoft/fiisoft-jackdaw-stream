<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Collector\Adapter\Iterable;

use FiiSoft\Jackdaw\Collector\BaseCollector;
use FiiSoft\Jackdaw\Collector\IterableCollector;
use FiiSoft\Jackdaw\Stream;

final class SplFixedArrayAdapter extends BaseCollector implements IterableCollector, \IteratorAggregate
{
    private \SplFixedArray $fixedArray;
    
    public function __construct(\SplFixedArray $fixedArray, ?bool $allowKeys = true)
    {
        parent::__construct($allowKeys);
        
        $this->fixedArray = $fixedArray;
    }
    
    public function set($key, $value): void
    {
        $this->fixedArray[$key] = $value;
    }
    
    public function add($value): void
    {
        $key = $this->fixedArray->key();
        $this->fixedArray[$key] = $value;
        $this->fixedArray->next();
    }
    
    public function count(): int
    {
        return \count($this->getData());
    }
    
    public function clear(): void
    {
        $size = $this->fixedArray->getSize();
        
        for ($i = 0; $i < $size; ++$i) {
            unset($this->fixedArray[$i]);
        }
        
        $this->fixedArray->rewind();
    }
    
    public function getData(): array
    {
        return \array_filter($this->fixedArray->toArray(), static fn($v): bool => $v !== null);
    }
    
    public function stream(): Stream
    {
        return Stream::from($this->fixedArray)->notNull();
    }
    
    public function toString(string $separator = ','): string
    {
        return \implode($separator, $this->getData());
    }
    
    public function toJson(int $flags = 0): string
    {
        return \json_encode($this->getData(), \JSON_THROW_ON_ERROR | $flags);
    }
    
    public function getIterator()
    {
        return $this->stream()->getIterator();
    }
}