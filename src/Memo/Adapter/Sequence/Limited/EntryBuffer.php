<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Memo\Adapter\Sequence\Limited;

use FiiSoft\Jackdaw\Internal\Destroyable;
use FiiSoft\Jackdaw\Memo\Entry;

/**
 * @extends \IteratorAggregate<string|int, mixed>
 */
interface EntryBuffer extends Destroyable, \IteratorAggregate
{
    /**
     * @param string|int $key
     * @param mixed $value
     */
    public function hold($key, $value): void;
    
    public function get(int $index): Entry;
    
    public function remove(int $index): Entry;

    /**
     * @return int number of items hold in buffer
     */
    public function count(): int;
    
    /**
     * Remove all collected items.
     */
    public function clear(): void;
    
    /**
     * @return array<string|int, mixed>
     */
    public function fetchData(): array;
    
    /**
     * @return array<mixed>
     */
    public function fetchValues(): array;
    
    /**
     * @return array<string|int>
     */
    public function fetchKeys(): array;
    
    /**
     * @return \Iterator<string|int, mixed>
     */
    public function getIterator(): \Iterator;
}