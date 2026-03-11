<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Memo;

use FiiSoft\Jackdaw\Exception\JackdawException;
use FiiSoft\Jackdaw\Internal\Destroyable;
use FiiSoft\Jackdaw\Mapper\MapperReady;
use FiiSoft\Jackdaw\Matcher\Matcher;
use FiiSoft\Jackdaw\Operation\Internal\ForkReady;
use FiiSoft\Jackdaw\Producer\ProducerReady;
use FiiSoft\Jackdaw\Reducer\Reducer;
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
     * It operates only on values. When sequence is empty, returns null.
     * Reducer is automatically reset on each call, so it behaves the same as callable.
     *
     * @param Reducer|callable $reducer Callable accepts two arguments: accumulator and value
     * @throws JackdawException when $reducer is neither Reducer nor a proper callable
     * @return mixed
     */
    public function reduce($reducer);
    
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