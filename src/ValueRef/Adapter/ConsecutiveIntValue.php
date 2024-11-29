<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\ValueRef\Adapter;

use FiiSoft\Jackdaw\ValueRef\Exception\WrongIntValueException;

final class ConsecutiveIntValue extends VolatileIntValue
{
    /** @var \Iterator<int> */
    private \Iterator $values;
    
    /**
     * @param iterable<int> $values
     */
    public function __construct(iterable $values, bool $loop)
    {
        $this->values = $this->createIterator($values, $loop);
        $this->values->rewind();
    }
    
    public function int(): int
    {
        if ($this->values->valid()) {
            $current = $this->values->current();
            $this->values->next();
            
            return $current;
        }
        
        throw WrongIntValueException::noMoreIntegersToIterateOver();
    }
    
    /**
     * @param iterable<int> $values
     */
    private function createIterator(iterable $values, bool $infinite): \Iterator
    {
        if ($infinite) {
            if ($values instanceof \Iterator) {
                return $values instanceof \InfiniteIterator ? $values : new \InfiniteIterator($values);
            }
            
            return $this->createGenerator($values, true);
        }
        
        return $values instanceof \Iterator ? $values : $this->createGenerator($values, false);
    }
    
    /**
     * @param iterable<int> $values
     */
    private function createGenerator(iterable $values, bool $infinite): \Iterator
    {
        do {
            yield from $values;
        } while ($infinite);
    }
}