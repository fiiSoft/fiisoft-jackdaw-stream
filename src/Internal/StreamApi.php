<?php

namespace FiiSoft\Jackdaw\Internal;

use FiiSoft\Jackdaw\Collector\Collector;
use FiiSoft\Jackdaw\Comparator\Comparator;
use FiiSoft\Jackdaw\Condition\Condition;
use FiiSoft\Jackdaw\Consumer\Consumer;
use FiiSoft\Jackdaw\Discriminator\Discriminator;
use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Handler\ErrorHandler;
use FiiSoft\Jackdaw\Mapper\Mapper;
use FiiSoft\Jackdaw\Operation\Internal\AssertionFailed;
use FiiSoft\Jackdaw\Predicate\Predicate;
use FiiSoft\Jackdaw\Producer\Producer;
use FiiSoft\Jackdaw\Reducer\Reducer;

interface StreamApi extends ResultCaster, \IteratorAggregate
{
    /**
     * @param Filter|Predicate|callable|mixed $filter
     * @param int $mode
     */
    public function filter($filter, int $mode = Check::VALUE): self;
    
    /**
     * @param string|int $field
     * @param Filter|Predicate|callable|mixed $filter
     */
    public function filterBy($field, $filter): self;
    
    /**
     * @param Filter|Predicate|callable|mixed $filter
     * @param int $mode
     */
    public function omit($filter, int $mode = Check::VALUE): self;
    
    /**
     * @param string|int $field
     * @param Filter|Predicate|callable|mixed $filter
     */
    public function omitBy($field, $filter): self;
    
    /**
     * @param float|int $value
     */
    public function greaterOrEqual($value): self;
    
    /**
     * @param float|int $value
     */
    public function greaterThan($value): self;
    
    /**
     * @param float|int $value
     */
    public function lessOrEqual($value): self;
    
    /**
     * @param float|int $value
     */
    public function lessThan($value): self;
    
    /**
     * Pass only numeric values
     */
    public function onlyNumeric(): self;
    
    /**
     * Pass only integer values
     */
    public function onlyIntegers(): self;
    
    /**
     * Pass only strings
     */
    public function onlyStrings(): self;
    
    /**
     * Assert that element in stream satisfies given requirements.
     * If not, it throws non-catchable exception.
     *
     * @param Filter|Predicate|callable|mixed $filter
     * @param int $mode
     * @throws AssertionFailed
     */
    public function assert($filter, int $mode = Check::VALUE): self;
    
    /**
     * Alias for map('trim').
     *
     * @return $this
     */
    public function trim(): self;
    
    /**
     * @param array $values
     * @param int $mode
     */
    public function without(array $values, int $mode = Check::VALUE): self;
    
    /**
     * @param array $values
     * @param int $mode
     */
    public function only(array $values, int $mode = Check::VALUE): self;
    
    /**
     * @param array|string|int $keys list of keys or single key
     * @param bool $allowNulls
     */
    public function onlyWith($keys, bool $allowNulls = false): self;
    
    /**
     * Pass only not-null values
     */
    public function notNull(): self;
    
    /**
     * Pas only not empty values
     */
    public function notEmpty(): self;
    
    /**
     * @param int $offset
     */
    public function skip(int $offset): self;
    
    /**
     * @param int $limit
     */
    public function limit(int $limit): self;
    
    /**
     * @param Consumer|callable|resource $consumers resource must be writeable
     */
    public function call(...$consumers): self;
    
    /**
     * @param Consumer|callable|resource $consumer
     */
    public function callOnce($consumer): self;
    
    /**
     * @param int $times
     * @param Consumer|callable|resource $consumer
     */
    public function callMax(int $times, $consumer): self;
    
    /**
     * @param Condition|Predicate|Filter|callable $condition
     * @param Consumer|callable|resource $consumer
     * @param Consumer|callable|resource|null $elseConsumer
     */
    public function callWhen($condition, $consumer, $elseConsumer = null): self;
    
    /**
     * It works exactly the same way as remap, it is only different syntax to use.
     * Processed value have to be an array.
     *
     * @param string|int $before old name of key
     * @param string|int $after new name of key
     */
    public function rename($before, $after): self;
    
    /**
     * Change names of keys in array-like values.
     * It requires array (or ArrayAccess) value to work with.
     *
     * @param array $keys map names of keys to rename (before => after)
     */
    public function remap(array $keys): self;
    
    /**
     * @param Mapper|Reducer|Predicate|Filter|callable|mixed $mapper
     */
    public function map($mapper): self;
    
    /**
     * @param Condition|Predicate|Filter|callable $condition
     * @param Mapper|Reducer|callable|mixed $mapper
     * @param Mapper|Reducer|callable|mixed|null $elseMapper
     */
    public function mapWhen($condition, $mapper, $elseMapper = null): self;
    
    /**
     * @param Mapper|callable $mapper
     */
    public function mapKey($mapper): self;
    
    /**
     * @param string|int $field
     * @param Mapper|Reducer|callable|mixed $mapper
     */
    public function mapField($field, $mapper): self;
    
    /**
     * @param string|int $field
     * @param Condition|Predicate|Filter|callable $condition
     * @param Mapper|Reducer|callable|mixed $mapper
     * @param Mapper|Reducer|callable|mixed|null $elseMapper
     */
    public function mapFieldWhen($field, $condition, $mapper, $elseMapper = null): self;
    
    /**
     * @param array|string|int|null
     */
    public function castToInt($fields = null): self;
    
    /**
     * @param array|string|int|null
     */
    public function castToFloat($fields = null): self;
    
    /**
     * @param array|string|int|null
     */
    public function castToString($fields = null): self;
    
    /**
     * @param array|string|int|null
     */
    public function castToBool($fields = null): self;
    
    /**
     * @param Collector|\ArrayAccess $collector
     * @param bool $preserveKeys (default false)
     */
    public function collectIn($collector, bool $preserveKeys = false): self;
    
    /**
     * @param Collector|\ArrayAccess $collector
     */
    public function collectKeys($collector): self;
    
    /**
     * @param StreamApi|Producer|Result|\Iterator|\PDOStatement|resource|array ...$producers
     */
    public function join(...$producers): self;
    
    /**
     * @param Comparator|callable|null $comparator
     * @param int $mode
     */
    public function unique($comparator = null, int $mode = Check::VALUE): self;
    
    /**
     * Reindex all keys for elements (0, 1, ...).
     * Optionally, it can start from different value and with different step (step cannot be 0).
     *
     * @param int $start initial value
     * @param int $step change value
     */
    public function reindex(int $start = 0, int $step = 1): self;
    
    /**
     * Flip values with keys
     */
    public function flip(): self;
    
    /**
     * @param mixed $initial
     * @param Reducer|callable $reducer
     */
    public function scan($initial, $reducer): self;
    
    /**
     * @param int $size
     * @param bool $preserveKeys
     */
    public function chunk(int $size, bool $preserveKeys = false): self;
    
    /**
     * It works the same way as chunk($size, true).
     *
     * @param int $size
     */
    public function chunkAssoc(int $size): self;
    
    /**
     * @param array $keys
     */
    public function aggregate(array $keys): self;
    
    /**
     * @param string|int $field
     * @param Mapper|Reducer|callable|mixed $mapper
     */
    public function append($field, $mapper): self;
    
    /**
     * @param string|int $field
     * @param Mapper|Reducer|callable|mixed $mapper
     */
    public function complete($field, $mapper): self;
    
    /**
     * @param string|int $field
     * @param string|int|null $key
     */
    public function moveTo($field, $key = null): self;
    
    /**
     * @param array|string|int $fields
     * @param mixed|null $orElse
     */
    public function extract($fields, $orElse = null): self;
    
    /**
     * @param Filter|Predicate|callable|mixed $filter
     * @param int $mode
     */
    public function extractWhen($filter, int $mode = Check::VALUE): self;
    
    /**
     * @param array|string|int $fields
     */
    public function remove(...$fields): self;
    
    /**
     * @param Filter|Predicate|callable|mixed $filter
     * @param int $mode
     */
    public function removeWhen($filter, int $mode = Check::VALUE): self;
    
    /**
     * @param string $separator
     */
    public function split(string $separator = ' '): self;
    
    /**
     * @param string $separtor
     */
    public function concat(string $separtor = ' '): self;
    
    /**
     * @param string $tokens
     */
    public function tokenize(string $tokens = ' '): self;
    
    /**
     * @param int $level
     */
    public function flat(int $level = 0): self;
    
    /**
     * @param Mapper|Reducer|callable|mixed $mapper
     * @param int $level
     */
    public function flatMap($mapper, int $level = 0): self;
    
    /**
     * @param string|int ...$fields fields to sort by, e.g. "name asc", "salary desc", 0, 3, "1 asc", "3 desc"
     */
    public function sortBy(...$fields): self;
    
    /**
     * Normal (ascending) sorting.
     *
     * @param Comparator|callable|null $comparator
     * @param int $mode
     */
    public function sort($comparator = null, int $mode = Check::VALUE): self;
    
    /**
     * Normal sorting with limited number of {$limit} first values passed further to stream.
     *
     * @param int $limit
     * @param Comparator|callable|null $comparator
     * @param int $mode
     */
    public function best(int $limit, $comparator = null, int $mode = Check::VALUE): self;
    
    /**
     * Reversed (descending) sorting.
     *
     * @param Comparator|callable|null $comparator
     * @param int $mode
     */
    public function rsort($comparator = null, int $mode = Check::VALUE): self;
    
    /**
     * Reversed sorting with limited number of {$limit} values passed further to stream.
     *
     * @param int $limit
     * @param Comparator|callable|null $comparator
     * @param int $mode
     */
    public function worst(int $limit, $comparator = null, int $mode = Check::VALUE): self;
    
    /**
     * Collect all incoming elements from stream and when there are no more elements,
     * reverse their order and start streaming again.
     */
    public function reverse(): self;
    
    /**
     * Collect all incoming elements from stream and when there are no more elements,
     * start streaming them again in randomized order.
     *
     * @param int|null $chunkSize when > 1 it collects and shuffles chunks of data
     */
    public function shuffle(?int $chunkSize = null): self;
    
    /**
     * @param StreamPipe ...$streams (or streems)
     */
    public function feed(StreamPipe ...$streams): self;
    
    /**
     * @param Filter|Predicate|callable|mixed $condition
     * @param int $mode
     */
    public function while($condition, int $mode = Check::VALUE): self;
    
    /**
     * @param Filter|Predicate|callable|mixed $condition
     * @param int $mode
     */
    public function until($condition, int $mode = Check::VALUE): self;
    
    /**
     * @param int $numOfItems number of Nth last elements
     */
    public function tail(int $numOfItems): self;
    
    /**
     * It works similar to chunk, but it gathers all elements until stream is empty,
     * and then passes whole array as argument for next step.
     *
     * @param bool $preserveKeys
     */
    public function gather(bool $preserveKeys = false): self;
    
    /**
     * It collects elements in array as long as they meet given condition.
     * With first element which does not meet condition, gathering values is aborted
     * and array of collected elements is passed to next step.
     * Any other elements from the stream will be ignored (they will never be read).
     *
     *
     * @param Filter|Predicate|callable|mixed $condition
     * @param int $mode
     * @param bool $preserveKeys
     */
    public function gatherWhile($condition, int $mode = Check::VALUE, bool $preserveKeys = false): self;
    
    /**
     * It collects elements in array until first element which does not meet given condition,
     * in which case gathering of values is aborted and array of collected elements is passed to next step.
     * Any other elements from the stream will be ignored (they will never be read).
     *
     * @param Filter|Predicate|callable|mixed $condition
     * @param int $mode
     * @param bool $preserveKeys
     */
    public function gatherUntil($condition, int $mode = Check::VALUE, bool $preserveKeys = false): self;
    
    /**
     * Replace value of current element with its [key, value].
     * When param $assoc is true then it creates pair ['key' => key, 'value' => value].
     * In both cases, real key of element is reindexed starting from 0 (like in reindex() operation).
     */
    public function makeTuple(bool $assoc = false): self;
    
    /**
     * Register handlers which will be called when error occurs.
     *
     * @param ErrorHandler|callable $handler it must return bool or null, see ErrorHandler
     * @param bool $replace when true then replace all existing handlers, when false then add handler to stack
     */
    public function onError($handler, bool $replace = false): self;
    
    /**
     * Register handlers which will be called at the end and only when no errors occurred.
     *
     * @param callable $handler
     * @param bool $replace when true then replace all existing handlers, when false then add handler to stack
     */
    public function onSuccess(callable $handler, bool $replace = false): self;
    
    /**
     * Register handlers which will be called at the end and regardless any errors occurred or not,
     * but not in the case when uncaught exception has been thrown!
     *
     * @param callable $handler
     * @param bool $replace when true then replace all existing handlers, when false then add handler to stack
     */
    public function onFinish(callable $handler, bool $replace = false): self;
    
    /**
     * @param Consumer|callable|resource $consumer
     * @return void
     */
    public function forEach(...$consumer): void;
    
    /**
     * Run stream pipeline.
     *
     * @return void
     */
    public function run(): void;
    
    /**
     * Feed stream recursively with its own output.
     *
     * @param bool $run when true then run immediately
     */
    public function loop(bool $run = false): StreamPipe;
    
    /**
     * Tell if element occurs in stream.
     *
     * @param Predicate|Filter|callable|mixed $value
     * @param int $mode
     * @return Result
     */
    public function has($value, int $mode = Check::VALUE): Result;
    
    /**
     * @param array $values
     * @param int $mode
     * @return Result
     */
    public function hasAny(array $values, int $mode = Check::VALUE): Result;
    
    /**
     * @param array $values
     * @param int $mode
     * @return Result
     */
    public function hasEvery(array $values, int $mode = Check::VALUE): Result;
    
    /**
     * @param array $values
     * @param int $mode
     * @return Result
     */
    public function hasOnly(array $values, int $mode = Check::VALUE): Result;
    
    /**
     * Tell if stream is not empty.
     *
     * @return Result
     */
    public function isNotEmpty(): Result;
    
    /**
     * Tell if stream is empty.
     *
     * @return Result
     */
    public function isEmpty(): Result;
    
    /**
     * Count elements in stream.
     *
     * @return Result
     */
    public function count(): Result;
    
    /**
     * Return first element in stream which satisfies given predicate.
     *
     * @param Predicate|Filter|callable|mixed $predicate
     * @param int $mode
     * @return Result found Item or null when element was not found
     */
    public function find($predicate, int $mode = Check::VALUE): Result;
    
    /**
     * Return first available element from stream.
     *
     * @param callable|mixed|null $orElse (null by default)
     * @return Result first value or default when stream is empty
     */
    public function first($orElse = null): Result;
    
    /**
     * Return last element from stream.
     *
     * @param callable|mixed|null $orElse (null by default)
     * @return Result last value from stream, or default when stream is empty
     */
    public function last($orElse = null): Result;
    
    /**
     * @param Reducer|callable $reducer
     * @param callable|mixed|null $orElse (default null)
     * @return Result
     */
    public function reduce($reducer, $orElse = null): Result;
    
    /**
     * @param mixed $initial
     * @param Reducer|callable $reducer
     * @return Result
     */
    public function fold($initial, $reducer): Result;
    
    /**
     * Collect all elements from stream.
     *
     * @return Result
     */
    public function collect(): Result;
    
    /**
     * Collect data as long as condition is true and finish processing when it is not.
     *
     * @param Filter|Predicate|callable|mixed $condition
     * @param int $mode
     */
    public function collectWhile($condition, int $mode = Check::VALUE): Result;
    
    /**
     * Collect data until condition is met and then finish processing.
     *
     * @param Filter|Predicate|callable|mixed $condition
     * @param int $mode
     */
    public function collectUntil($condition, int $mode = Check::VALUE): Result;
    
    /**
     * @param Discriminator|Condition|Predicate|Filter|string|callable $discriminator
     * @param bool $preserveKeys
     * @return StreamCollection
     */
    public function groupBy($discriminator, bool $preserveKeys = false): StreamCollection;
}