<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Collector\Adapter\Iterable;

use FiiSoft\Jackdaw\Stream;

final class SplFixedArrayAdapter extends BaseIterableCollector
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
    
    public function getIterator()
    {
        return $this->stream()->getIterator();
    }
}