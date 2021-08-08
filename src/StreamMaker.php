<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw;

use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Internal\Result;
use FiiSoft\Jackdaw\Internal\StreamApi;
use FiiSoft\Jackdaw\Internal\StreamCollection;
use FiiSoft\Jackdaw\Internal\StreamPipe;
use FiiSoft\Jackdaw\Producer\Producer;
use FiiSoft\Jackdaw\Producer\Producers;

final class StreamMaker implements StreamApi
{
    /** @var callable */
    private $factory;
    
    public static function of(...$elements): StreamApi
    {
        return self::from(Producers::from($elements));
    }
    
    public static function empty(): StreamApi
    {
        return self::from([]);
    }
    
    /**
     * @param Producer|\Iterator|array|callable $factory callable MUST return StreamApi
     * @return StreamApi
     */
    public static function from($factory): StreamApi
    {
        if (\is_array($factory)) {
            $callable = static function () use ($factory) {
                return Stream::from($factory);
            };
        } elseif ($factory instanceof Producer) {
            $callable = static function () use ($factory) {
                return Stream::from(clone $factory);
            };
        } elseif ($factory instanceof \Iterator) {
            $callable = static function () use ($factory) {
                return Stream::from(clone $factory);
            };
        } elseif (\is_callable($factory)) {
            $callable = $factory;
        } else {
            throw Helper::invalidParamException('factory', $factory);
        }
        
        return new self($callable);
    }
    
    /**
     * @param callable $factory this callable MUST return new StreamApi instance every time
     */
    public function __construct(callable $factory)
    {
        $this->factory = $factory;
    }
    
    private function make(): StreamApi
    {
        $factory = $this->factory;
        return $factory();
    }
    
    /**
     * @inheritdoc
     */
    public function notNull(): StreamApi
    {
        return $this->make()->notNull();
    }
    
    /**
     * @inheritdoc
     */
    public function lessOrEqual($value): StreamApi
    {
        return $this->make()->lessOrEqual($value);
    }
    
    /**
     * @inheritdoc
     */
    public function filter($filter, int $mode = Check::VALUE): StreamApi
    {
        return $this->make()->filter($filter, $mode);
    }
    
    /**
     * @inheritdoc
     */
    public function filterBy($field, $filter): StreamApi
    {
        return $this->make()->filterBy($field, $filter);
    }
    
    /**
     * @inheritdoc
     */
    public function skip(int $offset): StreamApi
    {
        return $this->make()->skip($offset);
    }
    
    /**
     * @inheritdoc
     */
    public function call($consumer): StreamApi
    {
        return $this->make()->call($consumer);
    }
    
    /**
     * @inheritdoc
     */
    public function callOnce($consumer): StreamApi
    {
        return $this->make()->callOnce($consumer);
    }
    
    /**
     * @inheritdoc
     */
    public function callMax(int $times, $consumer): StreamApi
    {
        return $this->make()->callMax($times, $consumer);
    }
    
    /**
     * @inheritdoc
     */
    public function callWhen($condition, $consumer, $elseConsumer = null): StreamApi
    {
        return $this->make()->callWhen($condition, $consumer, $elseConsumer);
    }
    
    /**
     * @inheritdoc
     */
    public function notEmpty(): StreamApi
    {
        return $this->make()->notEmpty();
    }
    
    /**
     * @inheritdoc
     */
    public function without(array $values, int $mode = Check::VALUE): StreamApi
    {
        return $this->make()->without($values, $mode);
    }
    
    /**
     * @inheritdoc
     */
    public function lessThan($value): StreamApi
    {
        return $this->make()->lessThan($value);
    }
    
    /**
     * @inheritdoc
     */
    public function greaterOrEqual($value): StreamApi
    {
        return $this->make()->greaterOrEqual($value);
    }
    
    /**
     * @inheritdoc
     */
    public function onlyNumeric(): StreamApi
    {
        return $this->make()->onlyNumeric();
    }
    
    /**
     * @inheritdoc
     */
    public function onlyIntegers(): StreamApi
    {
        return $this->make()->onlyIntegers();
    }
    
    /**
     * @inheritdoc
     */
    public function onlyStrings(): StreamApi
    {
        return $this->make()->onlyStrings();
    }
    
    /**
     * @inheritdoc
     */
    public function map($mapper): StreamApi
    {
        return $this->make()->map($mapper);
    }
    
    /**
     * @inheritdoc
     */
    public function mapWhen($condition, $mapper, $elseMapper = null): StreamApi
    {
        return $this->make()->mapWhen($condition, $mapper, $elseMapper);
    }
    
    /**
     * @inheritdoc
     */
    public function mapKey($mapper): StreamApi
    {
        return $this->make()->mapKey($mapper);
    }
    
    /**
     * @inheritdoc
     */
    public function castToInt($fields = null): StreamApi
    {
        return $this->make()->castToInt($fields);
    }
    
    /**
     * @inheritdoc
     */
    public function greaterThan($value): StreamApi
    {
        return $this->make()->greaterThan($value);
    }
    
    /**
     * @inheritdoc
     */
    public function collectIn($collector, bool $preserveKeys = false): StreamApi
    {
        return $this->make()->collectIn($collector, $preserveKeys);
    }
    
    /**
     * @inheritdoc
     */
    public function collectKeys($collector): StreamApi
    {
        return $this->make()->collectKeys($collector);
    }
    
    /**
     * @inheritdoc
     */
    public function onlyWith($keys, bool $allowNulls = false): StreamApi
    {
        return $this->make()->onlyWith($keys, $allowNulls);
    }
    
    /**
     * @inheritdoc
     */
    public function only(array $values, int $mode = Check::VALUE): StreamApi
    {
        return $this->make()->only($values, $mode);
    }
    
    /**
     * @inheritdoc
     */
    public function omit($filter, int $mode = Check::VALUE): StreamApi
    {
        return $this->make()->omit($filter, $mode);
    }
    
    /**
     * @inheritdoc
     */
    public function limit(int $limit): StreamApi
    {
        return $this->make()->limit($limit);
    }
    
    /**
     * @inheritdoc
     */
    public function join($producer): StreamApi
    {
        return $this->make()->join($producer);
    }
    
    /**
     * @inheritdoc
     */
    public function unique($comparator = null, int $mode = Check::VALUE): StreamApi
    {
        return $this->make()->unique($comparator, $mode);
    }
    
    /**
     * @inheritdoc
     */
    public function reindex(): StreamApi
    {
        return $this->make()->reindex();
    }
    
    /**
     * @inheritdoc
     */
    public function flip(): StreamApi
    {
        return $this->make()->flip();
    }
    
    /**
     * @inheritdoc
     */
    public function chunk(int $size, bool $preserveKeys = false): StreamApi
    {
        return $this->make()->chunk($size, $preserveKeys);
    }
    
    /**
     * @inheritdoc
     */
    public function chunkAssoc(int $size): StreamApi
    {
        return $this->make()->chunk($size, true);
    }
    
    public function aggregate(array $keys): StreamApi
    {
        return $this->make()->aggregate($keys);
    }
    
    /**
     * @inheritdoc
     */
    public function append($field, $mapper): StreamApi
    {
        return $this->make()->append($field, $mapper);
    }
    
    /**
     * @inheritdoc
     */
    public function complete($field, $mapper): StreamApi
    {
        return $this->make()->complete($field, $mapper);
    }
    
    /**
     * @inheritdoc
     */
    public function moveTo($field): StreamApi
    {
        return $this->make()->moveTo($field);
    }
    
    /**
     * @inheritdoc
     */
    public function extract($fields, $orElse = null): StreamApi
    {
        return $this->make()->extract($fields, $orElse);
    }
    
    /**
     * @inheritdoc
     */
    public function remove(...$fields): StreamApi
    {
        return $this->make()->remove(...$fields);
    }
    
    /**
     * @inheritdoc
     */
    public function split(string $separator = ' '): StreamApi
    {
        return $this->make()->split($separator);
    }
    
    /**
     * @inheritdoc
     */
    public function scan($initial, $reducer): StreamApi
    {
        return $this->make()->scan($initial, $reducer);
    }
    
    /**
     * @inheritdoc
     */
    public function flat(int $level = 0): StreamApi
    {
        return $this->make()->flat($level);
    }
    
    /**
     * @inheritdoc
     */
    public function flatMap($mapper, int $level = 0): StreamApi
    {
        return $this->make()->flatMap($mapper, $level);
    }
    
    /**
     * @inheritdoc
     */
    public function sortBy(...$fields): StreamApi
    {
        return $this->make()->sortBy(...$fields);
    }
    
    /**
     * @inheritdoc
     */
    public function sort($comparator = null, int $mode = Check::VALUE): StreamApi
    {
        return $this->make()->sort($comparator, $mode);
    }
    
    /**
     * @inheritdoc
     */
    public function best(int $limit, $comparator = null, int $mode = Check::VALUE): StreamApi
    {
        return $this->make()->best($limit, $comparator, $mode);
    }
    
    /**
     * @inheritdoc
     */
    public function rsort($comparator = null, int $mode = Check::VALUE): StreamApi
    {
        return $this->make()->rsort($comparator, $mode);
    }
    
    /**
     * @inheritdoc
     */
    public function worst(int $limit, $comparator = null, int $mode = Check::VALUE): StreamApi
    {
        return $this->make()->worst($limit, $comparator, $mode);
    }
    
    /**
     * @inheritdoc
     */
    public function reverse(): StreamApi
    {
        return $this->make()->reverse();
    }
    
    /**
     * @inheritdoc
     */
    public function shuffle(): StreamApi
    {
        return $this->make()->shuffle();
    }
    
    /**
     * @inheritdoc
     */
    public function feed(StreamPipe $stream): StreamApi
    {
        return $this->make()->feed($stream);
    }
    
    /**
     * @inheritdoc
     */
    public function while($condition, int $mode = Check::VALUE): StreamApi
    {
        return $this->make()->while($condition, $mode);
    }
    
    /**
     * @inheritdoc
     */
    public function until($condition, int $mode = Check::VALUE): StreamApi
    {
        return $this->make()->until($condition, $mode);
    }
    
    /**
     * @inheritdoc
     */
    public function tail(int $numOfItems): StreamApi
    {
        return $this->make()->tail($numOfItems);
    }
    
    /**
     * @inheritdoc
     */
    public function forEach($consumer)
    {
        $this->make()->forEach($consumer);
    }
    
    /**
     * @inheritdoc
     */
    public function reduce($reducer, $orElse = null): Result
    {
        return $this->make()->reduce($reducer, $orElse);
    }
    
    /**
     * @inheritdoc
     */
    public function fold($initial, $reducer): Result
    {
        return $this->make()->fold($initial, $reducer);
    }
    
    /**
     * @inheritdoc
     */
    public function groupBy($discriminator): StreamCollection
    {
        return $this->make()->groupBy($discriminator);
    }
    
    public function collect(): Result
    {
        return $this->make()->collect();
    }
    
    /**
     * @inheritdoc
     */
    public function count(): Result
    {
        return $this->make()->count();
    }
    
    /**
     * @inheritdoc
     */
    public function isEmpty(): Result
    {
        return $this->make()->isEmpty();
    }
    
    /**
     * @inheritdoc
     */
    public function isNotEmpty(): Result
    {
        return $this->make()->isNotEmpty();
    }
    
    /**
     * @inheritdoc
     */
    public function has($value, int $mode = Check::VALUE): Result
    {
        return $this->make()->has($value, $mode);
    }
    
    /**
     * @inheritdoc
     */
    public function hasAny(array $values, int $mode = Check::VALUE): Result
    {
        return $this->make()->hasAny($values, $mode);
    }
    
    /**
     * @inheritdoc
     */
    public function hasEvery(array $values, int $mode = Check::VALUE): Result
    {
        return $this->make()->hasEvery($values, $mode);
    }
    
    /**
     * @inheritdoc
     */
    public function hasOnly(array $values, int $mode = Check::VALUE): Result
    {
        return $this->make()->hasOnly($values, $mode);
    }
    
    /**
     * @inheritdoc
     */
    public function find($predicate, int $mode = Check::VALUE): Result
    {
        return $this->make()->find($predicate, $mode);
    }
    
    /**
     * @inheritdoc
     */
    public function toString(string $separator = ','): string
    {
        return $this->make()->toString($separator);
    }
    
    /**
     * @inheritdoc
     */
    public function toArray(bool $preserveKeys = false): array
    {
        return $this->make()->toArray($preserveKeys);
    }
    
    /**
     * @inheritdoc
     */
    public function toArrayAssoc(): array
    {
        return $this->make()->toArrayAssoc();
    }
    
    /**
     * @inheritdoc
     */
    public function toJson(int $flags = 0, bool $preserveKeys = false): string
    {
        return $this->make()->toJson($flags, $preserveKeys);
    }
    
    /**
     * @inheritdoc
     */
    public function toJsonAssoc(int $flags = 0): string
    {
        return $this->make()->toJsonAssoc($flags);
    }
    
    /**
     * @inheritdoc
     */
    public function first($orElse = null): Result
    {
        return $this->make()->first($orElse);
    }
    
    /**
     * @inheritdoc
     */
    public function last($orElse = null): Result
    {
        return $this->make()->last($orElse);
    }
    
    /**
     * @inheritdoc
     */
    public function getIterator()
    {
        return $this->make()->getIterator();
    }
    
    /**
     * @inheritdoc
     */
    public function run()
    {
        $this->make()->run();
    }
}