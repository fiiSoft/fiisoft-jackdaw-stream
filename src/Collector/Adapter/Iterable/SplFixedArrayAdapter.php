<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Collector\Adapter\Iterable;

use FiiSoft\Jackdaw\Stream;

final class SplFixedArrayAdapter extends BaseIterableCollector
{
    /** @var \SplFixedArray<mixed> */
    private \SplFixedArray $fixedArray;
    
    /** @var int<0, max> */
    private int $index = 0;
    
    private bool $valueSet = false;
    
    /**
     * @param \SplFixedArray<mixed> $fixedArray
     */
    public function __construct(\SplFixedArray $fixedArray, ?bool $allowKeys)
    {
        parent::__construct($allowKeys);
        
        $this->fixedArray = $fixedArray;
    }
    
    /**
     * @param int $key
     * @param mixed $value
     */
    public function set($key, $value): void
    {
        $this->fixedArray[$key] = $value;
        $this->valueSet = true;
    }
    
    public function add($value): void
    {
        $this->fixedArray[$this->index++] = $value;
    }
    
    public function count(): int
    {
        if (!$this->valueSet) {
            return $this->index;
        }
        
        $count = 0;
        
        foreach ($this->fixedArray as $value) {
            if ($value !== null) {
                ++$count;
            } else {
                break;
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
        $this->valueSet = false;
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