<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Memo;

use FiiSoft\Jackdaw\Producer\ProducerReady;
use FiiSoft\Jackdaw\Stream;

/**
 * @extends \IteratorAggregate<string|int, mixed>
 */
interface SequenceMemo extends MemoWriter, ProducerReady, \IteratorAggregate, \Countable
{
    public function get(int $index): Entry;
    
    public function key(int $index): MemoReader;
    
    public function value(int $index): MemoReader;
    
    public function tuple(int $index): MemoReader;
    
    public function pair(int $index): MemoReader;
    
    /**
     * @return mixed
     */
    public function valueOf(int $index);
    
    /**
     * @return mixed
     */
    public function keyOf(int $index);
    
    public function isFull(): bool;
    
    public function isEmpty(): bool;
    
    public function remove(int $index): Entry;
    
    public function clear(): void;
    
    /**
     * @return array<string|int, mixed>
     */
    public function toArray(): array;
    
    /**
     * @template T
     * @param T $initial
     * @param callable $reducer Callable accepts three arguments: accumulator, value, key
     * @return T
     */
    public function fold($initial, callable $reducer);
    
    /**
     * It operates only on values and value of the first element is assigned to accumulator as initial value,
     * so the sequence cannot be empty to call this operation!
     *
     * @param callable $reducer Callable accepts two arguments: accumulator and value
     * @return mixed
     */
    public function reduce(callable $reducer);
    
    public function stream(): Stream;
}