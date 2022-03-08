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
     * @return $this
     */
    public function filter($filter, int $mode = Check::VALUE): self;
    
    /**
     * @param string|int $field
     * @param Filter|Predicate|callable|mixed $filter
     * @return $this
     */
    public function filterBy($field, $filter): self;
    
    /**
     * @param Filter|Predicate|callable $filter
     * @param int $mode
     * @return $this
     */
    public function omit($filter, int $mode = Check::VALUE): self;
    
    /**
     * @param float|int $value
     * @return $this
     */
    public function greaterOrEqual($value): self;
    
    /**
     * @param float|int $value
     * @return $this
     */
    public function greaterThan($value): self;
    
    /**
     * @param float|int $value
     * @return $this
     */
    public function lessOrEqual($value): self;
    
    /**
     * @param float|int $value
     * @return $this
     */
    public function lessThan($value): self;
    
    /**
     * @return $this
     */
    public function onlyNumeric(): self;
    
    /**
     * @return $this
     */
    public function onlyIntegers(): self;
    
    /**
     * @return $this
     */
    public function onlyStrings(): self;
    
    /**
     * @param array $values
     * @param int $mode
     * @return $this
     */
    public function without(array $values, int $mode = Check::VALUE): self;
    
    /**
     * @param array $values
     * @param int $mode
     * @return $this
     */
    public function only(array $values, int $mode = Check::VALUE): self;
    
    /**
     * @param array|string|int $keys list of keys or single key
     * @param bool $allowNulls
     * @return $this
     */
    public function onlyWith($keys, bool $allowNulls = false): self;
    
    /**
     * @return $this
     */
    public function notNull(): self;
    
    /**
     * @return $this
     */
    public function notEmpty(): self;
    
    /**
     * @param int $offset
     * @return $this
     */
    public function skip(int $offset): self;
    
    /**
     * @param int $limit
     * @return $this
     */
    public function limit(int $limit): self;
    
    /**
     * @param Consumer|callable $consumers
     * @return $this
     */
    public function call(...$consumers): self;
    
    /**
     * @param Consumer|callable $consumer
     * @return $this
     */
    public function callOnce($consumer): self;
    
    /**
     * @param int $times
     * @param Consumer|callable $consumer
     * @return $this
     */
    public function callMax(int $times, $consumer): self;
    
    /**
     * @param Condition|Predicate|Filter|callable $condition
     * @param Consumer|callable $consumer
     * @param Consumer|callable|null $elseConsumer
     * @return $this
     */
    public function callWhen($condition, $consumer, $elseConsumer = null): self;
    
    /**
     * @param Mapper|callable $mapper
     * @return $this
     */
    public function map($mapper): self;
    
    /**
     * @param Condition|Predicate|Filter|callable $condition
     * @param Mapper|callable $mapper
     * @param Mapper|callable|null $elseMapper
     * @return $this
     */
    public function mapWhen($condition, $mapper, $elseMapper = null): self;
    
    /**
     * @param Mapper|callable $mapper
     * @return $this
     */
    public function mapKey($mapper): self;
    
    /**
     * @param array|string|int|null
     * @return $this
     */
    public function castToInt($fields = null): self;
    
    /**
     * @param Collector|\ArrayAccess $collector
     * @param bool $preserveKeys (default false)
     * @return $this
     */
    public function collectIn($collector, bool $preserveKeys = false): self;
    
    /**
     * @param Collector|\ArrayAccess $collector
     * @return $this
     */
    public function collectKeys($collector): self;
    
    /**
     * @param Stream|Producer|\Iterator|array $producer
     * @return $this
     */
    public function join($producer): self;
    
    /**
     * @param Comparator|callable|null $comparator
     * @param int $mode
     * @return $this
     */
    public function unique($comparator = null, int $mode = Check::VALUE): self;
    
    /**
     * @return $this
     */
    public function reindex(): self;
    
    /**
     * @return $this
     */
    public function flip(): self;
    
    /**
     * @param mixed $initial
     * @param Reducer|callable $reducer
     * @return $this
     */
    public function scan($initial, $reducer): self;
    
    /**
     * @param int $size
     * @param bool $preserveKeys
     * @return $this
     */
    public function chunk(int $size, bool $preserveKeys = false): self;
    
    /**
     * It works the same way as chunk($size, true).
     *
     * @param int $size
     * @return $this
     */
    public function chunkAssoc(int $size): self;
    
    /**
     * @param array $keys
     * @return $this
     */
    public function aggregate(array $keys): self;
    
    /**
     * @param string|int $field
     * @param Mapper|callable|mixed $mapper
     * @return $this
     */
    public function append($field, $mapper): self;
    
    /**
     * @param string|int $field
     * @param Mapper|callable|mixed $mapper
     * @return $this
     */
    public function complete($field, $mapper): self;
    
    /**
     * @param string|int $field
     * @return $this
     */
    public function moveTo($field): self;
    
    /**
     * @param array|string|int $fields
     * @param mixed|null $orElse
     * @return $this
     */
    public function extract($fields, $orElse = null): self;
    
    /**
     * @param array|string|int $fields
     * @return $this
     */
    public function remove(...$fields): self;
    
    /**
     * @param string $separator
     * @return $this
     */
    public function split(string $separator = ' '): self;
    
    /**
     * @param int $level
     * @return $this
     */
    public function flat(int $level = 0): self;
    
    /**
     * @param Mapper|callable $mapper
     * @param int $level
     * @return $this
     */
    public function flatMap($mapper, int $level = 0): self;
    
    /**
     * @param string ...$fields names of fields to sort by, in format "name asc", "salary desc"
     * @param int $limit last param can be integer, it means how many elements will be passed to stream
     * @return $this
     */
    public function sortBy(...$fields): self;
    
    /**
     * Normal (ascending) sorting.
     *
     * @param Comparator|callable|null $comparator
     * @param int $mode
     * @return $this
     */
    public function sort($comparator = null, int $mode = Check::VALUE): self;
    
    /**
     * Normal sorting with limited number of {$limit} first values passed further to stream.
     *
     * @param int $limit
     * @param Comparator|callable|null $comparator
     * @param int $mode
     * @return $this
     */
    public function best(int $limit, $comparator = null, int $mode = Check::VALUE): self;
    
    /**
     * Reversed (descending) sorting.
     *
     * @param Comparator|callable|null $comparator
     * @param int $mode
     * @return $this
     */
    public function rsort($comparator = null, int $mode = Check::VALUE): self;
    
    /**
     * Reversed sorting with limited number of {$limit} values passed further to stream.
     *
     * @param int $limit
     * @param Comparator|callable|null $comparator
     * @param int $mode
     * @return $this
     */
    public function worst(int $limit, $comparator = null, int $mode = Check::VALUE): self;
    
    /**
     * @return $this
     */
    public function reverse(): self;
    
    /**
     * @return $this
     */
    public function shuffle(): self;
    
    /**
     * @param BaseStreamPipe $stream
     * @return $this
     */
    public function feed(StreamPipe $stream): self;
    
    /**
     * @param Filter|Predicate|callable $condition
     * @param int $mode
     * @return $this
     */
    public function while($condition, int $mode = Check::VALUE): self;
    
    /**
     * @param Filter|Predicate|callable $condition
     * @param int $mode
     * @return $this
     */
    public function until($condition, int $mode = Check::VALUE): self;
    
    /**
     * @param int $numOfItems number of Nth last elements
     * @return $this
     */
    public function tail(int $numOfItems): self;
    
    /**
     * Register handlers which will be called when error occurs.
     *
     * @param ErrorHandler|callable $handler it must return bool or null, see ErrorHandler
     * @param bool $replace when true then replace all existing handlers, when false then add handler to stack
     * @return $this
     */
    public function onError($handler, bool $replace = false): self;
    
    /**
     * Register handlers which will be called at the end and only when no errors occurred.
     *
     * @param callable $handler
     * @param bool $replace when true then replace all existing handlers, when false then add handler to stack
     * @return $this
     */
    public function onSuccess(callable $handler, bool $replace = false): self;
    
    /**
     * Register handlers which will be called at the end and regardless any errors occurred or not,
     * but not in the case when uncaught exception has been thrown!
     *
     * @param callable $handler
     * @param bool $replace when true then replace all existing handlers, when false then add handler to stack
     * @return $this
     */
    public function onFinish(callable $handler, bool $replace = false): self;
    
    /**
     * @param Consumer|callable $consumer
     * @return void
     */
    public function forEach($consumer): void;
    
    /**
     * @return void
     */
    public function run(): void;
    
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
     * @return Result
     */
    public function isNotEmpty(): Result;
    
    /**
     * @return Result
     */
    public function isEmpty(): Result;
    
    /**
     * @return Result
     */
    public function count(): Result;
    
    /**
     * @param Predicate|Filter|callable|mixed $predicate
     * @param int $mode
     * @return Result found Item or null when element was not found
     */
    public function find($predicate, int $mode = Check::VALUE): Result;
    
    /**
     * @param mixed $orElse (null by default)
     * @return Result first value or default when stream is empty
     */
    public function first($orElse = null): Result;
    
    /**
     * @param mixed $orElse (null by default)
     * @return Result last value from stream, or default when stream is empty
     */
    public function last($orElse = null): Result;
    
    /**
     * @param Reducer|callable $reducer
     * @param mixed|null $orElse (default null)
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
     * @return Result
     */
    public function collect(): Result;
    
    /**
     * @param Discriminator|Condition|Predicate|Filter|string|callable $discriminator
     * @return StreamCollection
     */
    public function groupBy($discriminator): StreamCollection;
}