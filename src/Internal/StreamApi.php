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
use FiiSoft\Jackdaw\Predicate\Predicate;
use FiiSoft\Jackdaw\Producer\Producer;
use FiiSoft\Jackdaw\Reducer\Reducer;
use FiiSoft\Jackdaw\Stream;

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
     * @param Filter|Predicate|callable $filter
     * @param int $mode
     */
    public function omit($filter, int $mode = Check::VALUE): self;
    
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
     * @param Mapper|Reducer|callable|mixed $mapper
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
     * @param StreamApi|Producer|\Iterator|\PDOStatement|resource|array $producer
     */
    public function join($producer): self;
    
    /**
     * @param Comparator|callable|null $comparator
     * @param int $mode
     */
    public function unique($comparator = null, int $mode = Check::VALUE): self;
    
    /**
     * Reindex all keys for elements (0, 1, ...)
     */
    public function reindex(): self;
    
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
     */
    public function moveTo($field): self;
    
    /**
     * @param array|string|int $fields
     * @param mixed|null $orElse
     */
    public function extract($fields, $orElse = null): self;
    
    /**
     * @param array|string|int $fields
     */
    public function remove(...$fields): self;
    
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
     * @param Mapper|callable $mapper
     * @param int $level
     */
    public function flatMap($mapper, int $level = 0): self;
    
    /**
     * @param string ...$fields names of fields to sort by, in format "name asc", "salary desc"
     * @param int $limit last param can be integer, it means how many elements will be passed to stream
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
     */
    public function shuffle(): self;
    
    /**
     * @param StreamPipe $stream it MUST be empty stream - created by Stream::empty()
     */
    public function feed(StreamPipe $stream): self;
    
    /**
     * @param Filter|Predicate|callable $condition
     * @param int $mode
     */
    public function while($condition, int $mode = Check::VALUE): self;
    
    /**
     * @param Filter|Predicate|callable $condition
     * @param int $mode
     */
    public function until($condition, int $mode = Check::VALUE): self;
    
    /**
     * @param int $numOfItems number of Nth last elements
     */
    public function tail(int $numOfItems): self;
    
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
     * @return void
     */
    public function run(): void;
    
    /**
     * Feed stream recusrively with its own output.
     */
    public function loop(): StreamPipe;
    
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
     * @param Discriminator|Condition|Predicate|Filter|string|callable $discriminator
     * @param bool $preserveKeys
     * @return StreamCollection
     */
    public function groupBy($discriminator, bool $preserveKeys = false): StreamCollection;
}