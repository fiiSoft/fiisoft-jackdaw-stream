<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Memo;

use FiiSoft\Jackdaw\Internal\Destroyable;
use FiiSoft\Jackdaw\Mapper\MapperReady;
use FiiSoft\Jackdaw\Matcher\Matcher;
use FiiSoft\Jackdaw\Operation\Collecting\ForkReady;
use FiiSoft\Jackdaw\Producer\ProducerReady;
use FiiSoft\Jackdaw\Stream;
use FiiSoft\Jackdaw\Transformer\TransformerReady;

/**
 * @extends \IteratorAggregate<string|int, mixed>
 */
interface SequenceMemo extends MemoWriter, ForkReady, ProducerReady, MapperReady, TransformerReady, Destroyable,
    \IteratorAggregate
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
    
    public function count(): int;
    
    public function isFull(): bool;
    
    public function isEmpty(): bool;
    
    public function remove(int $index): Entry;
    
    public function clear(): void;
    
    /**
     * @return array<string|int, mixed>
     */
    public function toArray(): array;
    
    /**
     * Fetch only values, indexed numerically.
     *
     * @return array<mixed>
     */
    public function getValues(): array;
    
    /**
     * Fetch only keys, indexed numerically.
     *
     * @return array<string|int>
     */
    public function getKeys(): array;
    
    /**
     * @template T
     * @param T $initial
     * @param callable(T, mixed, string|int): T $reducer Callable accepts three arguments: accumulator, value, key
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
    
    /**
     * @param SequenceInspector|callable(SequenceMemo): bool $inspector callable gets SequenceMemo as the argument
     *                                                                  and must return bool
     */
    public function inspect($inspector): SequencePredicate;
    
    /**
     * When callable $matcher returns int, it must work like spaceship operator, so 0 means compared values are equal.
     *
     * When callable $matcher accepts two arguments, the order of arguments passed to it is:
     * (sequenceValue, patternValue), or (sequenceKey, patternKey) - depending on Matcher's operating mode.
     *
     * When callable $matcher accepts four arguments, the order of arguments passed to it is:
     * (sequenceValue, patternValue, sequenceKey, patternKey).
     *
     * When $matcher is NULL then sequence and $pattern elements are compared by their values only.
     *
     * @param array<string|int, mixed> $pattern
     * @param Matcher|callable|null $matcher callable must accept two or four arguments and return bool or int
     */
    public function matches(array $pattern, $matcher = null): SequencePredicate;
    
    public function stream(): Stream;
}