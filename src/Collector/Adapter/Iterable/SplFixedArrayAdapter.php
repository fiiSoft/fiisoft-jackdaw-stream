<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Collector\Adapter\Iterable;

use FiiSoft\Jackdaw\Stream;

final class SplFixedArrayAdapter extends BaseIterableCollector
{
    private \SplFixedArray $fixedArray;
    
    private int $index = 0, $count = 0;
    
    public function __construct(\SplFixedArray $fixedArray, ?bool $allowKeys = true)
    {
        parent::__construct($allowKeys);
        
        $this->fixedArray = $fixedArray;
    }
    
    public function set($key, $value): void
    {
        $this->fixedArray[$key] = $value;
        ++$this->count;
    }
    
    public function add($value): void
    {
        $this->fixedArray[$this->index++] = $value;
        ++$this->count;
    }
    
    public function count(): int
    {
        if ($this->count === $this->index) {
            return $this->count;
        }
        
        $count = 0;
        
        foreach ($this->fixedArray as $value) {
            if ($value !== null) {
                ++$count;
            }
        }
        
        return $count;
    }
    
    public function clear(): void
    {
        $size = $this->fixedArray->getSize();
        
        for ($i = 0; $i < $size; ++$i) {
            unset($this->fixedArray[$i]);
        }
        
        $this->index = 0;
        $this->count = 0;
    }
    
    public function toArray(): array
    {
        return \iterator_to_array($this->getIterator());
    }
    
    public function stream(): Stream
    {
        return Stream::from($this->getIterator());
    }
    
    public function getIterator(): \Traversable
    {
        $iterator = function (): \Generator {
            foreach ($this->fixedArray as $key => $value) {
                if ($value !== null) {
                    yield $key => $value;
                }
            }
        };
        
        return $iterator();
    }
}