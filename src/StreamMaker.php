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
    
    /**
     * @param StreamApi|Producer|Result|\Iterator|\PDOStatement|resource|array|scalar ...$elements
     * @return self
     */
    public static function of(...$elements): self
    {
        return self::from(Producers::from($elements));
    }
    
    /**
     * @param Producer|\Iterator|Result|array|callable $factory callable MUST return Stream
     * @return self
     */
    public static function from($factory): self
    {
        if (\is_array($factory)) {
            $callable = static fn(): Stream => Stream::from($factory);
        } elseif ($factory instanceof Producer) {
            $callable = static fn(): Stream => Stream::from(clone $factory);
        } elseif ($factory instanceof Result) {
            $callable = static fn(): Stream => Stream::from($factory);
        } elseif ($factory instanceof \Iterator) {
            $callable = static fn(): Stream => Stream::from(clone $factory);
        } elseif (\is_callable($factory)) {
            $callable = $factory;
        } else {
            throw Helper::invalidParamException('factory', $factory);
        }
        
        return new self($callable);
    }
    
    public static function empty(): self
    {
        return self::from(static fn(): Stream => Stream::empty());
    }
    
    /**
     * @param callable $factory this callable MUST return new Stream instance every time
     */
    public function __construct(callable $factory)
    {
        $this->factory = $factory;
    }
    
    private function make(): Stream
    {
        $factory = $this->factory;
        return $factory();
    }
    
    /**
     * @inheritdoc
     */
    public function notNull(): Stream
    {
        return $this->make()->notNull();
    }
    
    /**
     * @inheritdoc
     */
    public function lessOrEqual($value): Stream
    {
        return $this->make()->lessOrEqual($value);
    }
    
    /**
     * @inheritdoc
     */
    public function filter($filter, int $mode = Check::VALUE): Stream
    {
        return $this->make()->filter($filter, $mode);
    }
    
    /**
     * @inheritdoc
     */
    public function filterBy($field, $filter): Stream
    {
        return $this->make()->filterBy($field, $filter);
    }
    
    /**
     * @inheritdoc
     */
    public function extractWhen($filter, int $mode = Check::VALUE): Stream
    {
        return $this->make()->extractWhen($filter, $mode);
    }
    
    /**
     * @inheritdoc
     */
    public function skip(int $offset): Stream
    {
        return $this->make()->skip($offset);
    }
    
    /**
     * @inheritdoc
     */
    public function call(...$consumers): Stream
    {
        return $this->make()->call(...$consumers);
    }
    
    /**
     * @inheritdoc
     */
    public function callOnce($consumer): Stream
    {
        return $this->make()->callOnce($consumer);
    }
    
    /**
     * @inheritdoc
     */
    public function callMax(int $times, $consumer): Stream
    {
        return $this->make()->callMax($times, $consumer);
    }
    
    /**
     * @inheritdoc
     */
    public function callWhen($condition, $consumer, $elseConsumer = null): Stream
    {
        return $this->make()->callWhen($condition, $consumer, $elseConsumer);
    }
    
    /**
     * @inheritdoc
     */
    public function notEmpty(): Stream
    {
        return $this->make()->notEmpty();
    }
    
    /**
     * @inheritdoc
     */
    public function without(array $values, int $mode = Check::VALUE): Stream
    {
        return $this->make()->without($values, $mode);
    }
    
    /**
     * @inheritdoc
     */
    public function lessThan($value): Stream
    {
        return $this->make()->lessThan($value);
    }
    
    /**
     * @inheritdoc
     */
    public function greaterOrEqual($value): Stream
    {
        return $this->make()->greaterOrEqual($value);
    }
    
    /**
     * @inheritdoc
     */
    public function onlyNumeric(): Stream
    {
        return $this->make()->onlyNumeric();
    }
    
    /**
     * @inheritdoc
     */
    public function onlyIntegers(): Stream
    {
        return $this->make()->onlyIntegers();
    }
    
    /**
     * @inheritdoc
     */
    public function onlyStrings(): Stream
    {
        return $this->make()->onlyStrings();
    }
    
    /**
     * @inheritdoc
     */
    public function assert($filter, int $mode = Check::VALUE): Stream
    {
        return $this->make()->assert($filter, $mode);
    }
    
    /**
     * @inheritdoc
     */
    public function trim(): Stream
    {
        return $this->make()->trim();
    }
    
    /**
     * @inheritdoc
     */
    public function remap(array $keys): Stream
    {
        return $this->make()->remap($keys);
    }
    
    /**
     * @inheritdoc
     */
    public function rename($before, $after): Stream
    {
        return $this->make()->rename($before, $after);
    }
    
    /**
     * @inheritdoc
     */
    public function map($mapper): Stream
    {
        return $this->make()->map($mapper);
    }
    
    /**
     * @inheritdoc
     */
    public function mapField($field, $mapper): Stream
    {
        return $this->make()->mapField($field, $mapper);
    }
    
    /**
     * @inheritdoc
     */
    public function mapFieldWhen($field, $condition, $mapper, $elseMapper = null): Stream
    {
        return $this->make()->mapFieldWhen($field, $condition, $mapper, $elseMapper);
    }
    
    /**
     * @inheritdoc
     */
    public function mapWhen($condition, $mapper, $elseMapper = null): Stream
    {
        return $this->make()->mapWhen($condition, $mapper, $elseMapper);
    }
    
    /**
     * @inheritdoc
     */
    public function mapKey($mapper): Stream
    {
        return $this->make()->mapKey($mapper);
    }
    
    /**
     * @inheritdoc
     */
    public function castToInt($fields = null): Stream
    {
        return $this->make()->castToInt($fields);
    }
    
    /**
     * @inheritdoc
     */
    public function castToFloat($fields = null): Stream
    {
        return $this->make()->castToFloat($fields);
    }
    
    /**
     * @inheritdoc
     */
    public function castToString($fields = null): Stream
    {
        return $this->make()->castToString($fields);
    }
    
    /**
     * @inheritdoc
     */
    public function castToBool($fields = null): Stream
    {
        return $this->make()->castToBool($fields);
    }
    
    /**
     * @inheritdoc
     */
    public function greaterThan($value): Stream
    {
        return $this->make()->greaterThan($value);
    }
    
    /**
     * @inheritdoc
     */
    public function collectIn($collector, bool $preserveKeys = false): Stream
    {
        return $this->make()->collectIn($collector, $preserveKeys);
    }
    
    /**
     * @inheritdoc
     */
    public function collectKeys($collector): Stream
    {
        return $this->make()->collectKeys($collector);
    }
    
    /**
     * @inheritdoc
     */
    public function onlyWith($keys, bool $allowNulls = false): Stream
    {
        return $this->make()->onlyWith($keys, $allowNulls);
    }
    
    /**
     * @inheritdoc
     */
    public function only(array $values, int $mode = Check::VALUE): Stream
    {
        return $this->make()->only($values, $mode);
    }
    
    /**
     * @inheritdoc
     */
    public function omit($filter, int $mode = Check::VALUE): Stream
    {
        return $this->make()->omit($filter, $mode);
    }
    
    /**
     * @inheritdoc
     */
    public function omitBy($field, $filter): Stream
    {
        return $this->make()->omitBy($field, $filter);
    }
    
    /**
     * @inheritdoc
     */
    public function removeWhen($filter, int $mode = Check::VALUE): Stream
    {
        return $this->make()->removeWhen($filter, $mode);
    }
    
    /**
     * @inheritdoc
     */
    public function limit(int $limit): Stream
    {
        return $this->make()->limit($limit);
    }
    
    /**
     * @inheritdoc
     */
    public function join(...$producers): Stream
    {
        return $this->make()->join(...$producers);
    }
    
    /**
     * @inheritdoc
     */
    public function unique($comparator = null, int $mode = Check::VALUE): Stream
    {
        return $this->make()->unique($comparator, $mode);
    }
    
    /**
     * @inheritdoc
     */
    public function reindex(int $start = 0, int $step = 1): Stream
    {
        return $this->make()->reindex($start, $step);
    }
    
    /**
     * @inheritdoc
     */
    public function flip(): Stream
    {
        return $this->make()->flip();
    }
    
    /**
     * @inheritdoc
     */
    public function chunk(int $size, bool $preserveKeys = false): Stream
    {
        return $this->make()->chunk($size, $preserveKeys);
    }
    
    /**
     * @inheritdoc
     */
    public function chunkAssoc(int $size): Stream
    {
        return $this->make()->chunk($size, true);
    }
    
    public function aggregate(array $keys): Stream
    {
        return $this->make()->aggregate($keys);
    }
    
    /**
     * @inheritdoc
     */
    public function append($field, $mapper): Stream
    {
        return $this->make()->append($field, $mapper);
    }
    
    /**
     * @inheritdoc
     */
    public function complete($field, $mapper): Stream
    {
        return $this->make()->complete($field, $mapper);
    }
    
    /**
     * @inheritdoc
     */
    public function moveTo($field, $key = null): Stream
    {
        return $this->make()->moveTo($field, $key);
    }
    
    /**
     * @inheritdoc
     */
    public function extract($fields, $orElse = null): Stream
    {
        return $this->make()->extract($fields, $orElse);
    }
    
    /**
     * @inheritdoc
     */
    public function remove(...$fields): Stream
    {
        return $this->make()->remove(...$fields);
    }
    
    /**
     * @inheritdoc
     */
    public function split(string $separator = ' '): Stream
    {
        return $this->make()->split($separator);
    }
    
    /**
     * @inheritdoc
     */
    public function concat(string $separtor = ' '): Stream
    {
        return $this->make()->concat($separtor);
    }
    
    /**
     * @inheritdoc
     */
    public function tokenize(string $tokens = ' '): Stream
    {
        return $this->make()->tokenize($tokens);
    }
    
    /**
     * @inheritdoc
     */
    public function scan($initial, $reducer): Stream
    {
        return $this->make()->scan($initial, $reducer);
    }
    
    /**
     * @inheritdoc
     */
    public function flat(int $level = 0): Stream
    {
        return $this->make()->flat($level);
    }
    
    /**
     * @inheritdoc
     */
    public function flatMap($mapper, int $level = 0): Stream
    {
        return $this->make()->flatMap($mapper, $level);
    }
    
    /**
     * @inheritdoc
     */
    public function sortBy(...$fields): Stream
    {
        return $this->make()->sortBy(...$fields);
    }
    
    /**
     * @inheritdoc
     */
    public function sort($comparator = null, int $mode = Check::VALUE): Stream
    {
        return $this->make()->sort($comparator, $mode);
    }
    
    /**
     * @inheritdoc
     */
    public function best(int $limit, $comparator = null, int $mode = Check::VALUE): Stream
    {
        return $this->make()->best($limit, $comparator, $mode);
    }
    
    /**
     * @inheritdoc
     */
    public function rsort($comparator = null, int $mode = Check::VALUE): Stream
    {
        return $this->make()->rsort($comparator, $mode);
    }
    
    /**
     * @inheritdoc
     */
    public function worst(int $limit, $comparator = null, int $mode = Check::VALUE): Stream
    {
        return $this->make()->worst($limit, $comparator, $mode);
    }
    
    /**
     * @inheritdoc
     */
    public function reverse(): Stream
    {
        return $this->make()->reverse();
    }
    
    /**
     * @inheritdoc
     */
    public function shuffle(?int $chunkSize = null): Stream
    {
        return $this->make()->shuffle($chunkSize);
    }
    
    /**
     * @inheritdoc
     */
    public function feed(StreamPipe ...$streams): Stream
    {
        return $this->make()->feed(...$streams);
    }
    
    /**
     * @inheritdoc
     */
    public function while($condition, int $mode = Check::VALUE): Stream
    {
        return $this->make()->while($condition, $mode);
    }
    
    /**
     * @inheritdoc
     */
    public function until($condition, int $mode = Check::VALUE): Stream
    {
        return $this->make()->until($condition, $mode);
    }
    
    /**
     * @inheritdoc
     */
    public function tail(int $numOfItems): Stream
    {
        return $this->make()->tail($numOfItems);
    }
    
    /**
     * @inheritdoc
     */
    public function gather(bool $preserveKeys = false): Stream
    {
        return $this->make()->gather($preserveKeys);
    }
    
    /**
     * @inheritdoc
     */
    public function gatherWhile($condition, int $mode = Check::VALUE, bool $preserveKeys = false): Stream
    {
        return $this->make()->gatherWhile($condition, $mode, $preserveKeys);
    }
    
    /**
     * @inheritdoc
     */
    public function gatherUntil($condition, int $mode = Check::VALUE, bool $preserveKeys = false): Stream
    {
        return $this->make()->gatherUntil($condition, $mode, $preserveKeys);
    }
    
    /**
     * @inheritdoc
     */
    public function makeTuple(bool $assoc = false): Stream
    {
        return $this->make()->makeTuple($assoc);
    }
    
    /**
     * @inheritdoc
     */
    public function onError($handler, bool $replace = false): Stream
    {
        return $this->make()->onError($handler, $replace);
    }
    
    /**
     * @inheritdoc
     */
    public function onSuccess(callable $handler, bool $replace = false): Stream
    {
        return $this->make()->onSuccess($handler, $replace);
    }
    
    /**
     * @inheritdoc
     */
    public function onFinish(callable $handler, bool $replace = false): Stream
    {
        return $this->make()->onFinish($handler, $replace);
    }
    
    /**
     * @inheritdoc
     */
    public function forEach(...$consumer): void
    {
        $this->make()->forEach(...$consumer);
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
    public function groupBy($discriminator, bool $preserveKeys = false): StreamCollection
    {
        return $this->make()->groupBy($discriminator, $preserveKeys);
    }
    
    /**
     * @inheritdoc
     */
    public function collect(): Result
    {
        return $this->make()->collect();
    }
    
    /**
     * @inheritdoc
     */
    public function collectWhile($condition, int $mode = Check::VALUE): Result
    {
        return $this->make()->collectWhile($condition, $mode);
    }
    
    /**
     * @inheritdoc
     */
    public function collectUntil($condition, int $mode = Check::VALUE): Result
    {
        return $this->make()->collectUntil($condition, $mode);
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
    public function run(): void
    {
        $this->make()->run();
    }
    
    /**
     * @inheritdoc
     */
    public function loop(bool $run = false): StreamPipe
    {
        return $this->make()->loop($run);
    }
}