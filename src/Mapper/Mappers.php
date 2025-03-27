<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Discriminator\Discriminator;
use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Internal\ResultCaster;
use FiiSoft\Jackdaw\Mapper\Adapter\DiscriminatorAdapter;
use FiiSoft\Jackdaw\Mapper\Adapter\FilterAdapter;
use FiiSoft\Jackdaw\Mapper\Adapter\GeneratorAdapter;
use FiiSoft\Jackdaw\Mapper\Adapter\MemoReaderAdapter;
use FiiSoft\Jackdaw\Mapper\Adapter\ProducerAdapter;
use FiiSoft\Jackdaw\Mapper\Adapter\ReducerAdapter;
use FiiSoft\Jackdaw\Mapper\Adapter\SequenceMemoAdapter;
use FiiSoft\Jackdaw\Mapper\Cast\ToArray;
use FiiSoft\Jackdaw\Mapper\Cast\ToBool;
use FiiSoft\Jackdaw\Mapper\Cast\ToFloat;
use FiiSoft\Jackdaw\Mapper\Cast\ToInt;
use FiiSoft\Jackdaw\Mapper\Cast\ToString;
use FiiSoft\Jackdaw\Mapper\Cast\ToTime;
use FiiSoft\Jackdaw\Mapper\Internal\MultiMapper;
use FiiSoft\Jackdaw\Mapper\ReindexKeys\ReindexKeysComplex;
use FiiSoft\Jackdaw\Mapper\ReindexKeys\ReindexKeysSimple;
use FiiSoft\Jackdaw\Memo\MemoReader;
use FiiSoft\Jackdaw\Memo\SequenceMemo;
use FiiSoft\Jackdaw\Producer\Producer;
use FiiSoft\Jackdaw\Reducer\Reducer;

final class Mappers
{
    /**
     * @param MapperReady|callable|iterable|mixed $mapper
     */
    public static function getAdapter($mapper): Mapper
    {
        if ($mapper instanceof Mapper) {
            return $mapper;
        }
    
        if (\is_callable($mapper)) {
            if (\is_string($mapper)) {
                switch ($mapper) {
                    case 'intval':
                    case '\intval':
                        return self::toInt();
                    case 'strval':
                    case '\strval':
                        return self::toString();
                    case 'floatval':
                    case '\floatval':
                        return self::toFloat();
                    case 'boolval':
                    case '\boolval':
                        return self::toBool();
                    case 'implode':
                    case '\implode':
                        return self::concat();
                    case 'explode':
                    case '\explode':
                        return self::split();
                    case 'array_reverse':
                    case '\array_reverse':
                        return self::reverse();
                    case 'json_encode':
                    case '\json_encode':
                        return self::jsonEncode();
                    case 'json_decode':
                    case '\json_decode':
                        return self::jsonDecode();
                    case 'trim':
                    case '\trim':
                        return self::trim();
                    case 'shuffle':
                    case '\shuffle':
                    case 'str_shuffle':
                    case '\str_shuffle':
                        return self::shuffle();
                    case '\array_values':
                    case 'array_values':
                        return self::reindexKeys();
                    default:
                        //noop
                }
            }
            
            return GenericMapper::create($mapper);
        }
    
        if ($mapper instanceof Reducer) {
            return new ReducerAdapter($mapper);
        }
    
        if ($mapper instanceof Filter) {
            return new FilterAdapter($mapper);
        }
        
        if ($mapper instanceof MemoReader) {
            return new MemoReaderAdapter($mapper);
        }
        
        if ($mapper instanceof SequenceMemo) {
            return new SequenceMemoAdapter($mapper);
        }
        
        if ($mapper instanceof Discriminator) {
            return new DiscriminatorAdapter($mapper);
        }
        
        if ($mapper instanceof ResultCaster) {
            return new GeneratorAdapter((static function () use ($mapper): \Generator {
                foreach ($mapper->toArrayAssoc() as $key => $value) {
                    yield $key => $value;
                }
            })());
        }
        
        if ($mapper instanceof Producer) {
            return new ProducerAdapter($mapper);
        }
        
        if ($mapper instanceof \Generator) {
            return new GeneratorAdapter($mapper);
        }
        
        if ($mapper instanceof \Traversable) {
            return new GeneratorAdapter((static function () use ($mapper) {
                foreach ($mapper as $key => $value) {
                    yield $key => $value;
                }
            })());
        }
        
        if (\is_array($mapper)) {
            return new MultiMapper($mapper);
        }
        
        return self::simple($mapper);
    }
    
    /**
     * @param array<string|int>|string|int|null $fields
     */
    public static function toInt($fields = null): Mapper
    {
        return ToInt::create($fields);
    }
    
    /**
     * @param array<string|int>|string|int|null $fields
     */
    public static function toString($fields = null): Mapper
    {
        return ToString::create($fields);
    }
    
    /**
     * @param array<string|int>|string|int|null $fields
     */
    public static function toFloat($fields = null): Mapper
    {
        return ToFloat::create($fields);
    }
    
    /**
     * @param array<string|int>|string|int|null $fields
     */
    public static function toBool($fields = null): Mapper
    {
        return ToBool::create($fields);
    }
    
    /**
     * @param array<string|int>|string|int|null $fields
     * @param \DateTimeZone|string|null $inTimeZone
     */
    public static function toTime($fields = null, ?string $fromFormat = null, $inTimeZone = null): Mapper
    {
        return ToTime::create($fields, $fromFormat, $inTimeZone);
    }
    
    public static function toArray(bool $appendKey = false): Mapper
    {
        return new ToArray($appendKey);
    }
    
    public static function concat(string $separator = ''): Mapper
    {
        return new Concat($separator);
    }
    
    /**
     * It works with strings and produces arrays. Internally, it's a wrapper for \explode().
     */
    public static function split(string $separator = ' '): Mapper
    {
        return new Split($separator);
    }
    
    /**
     * It works with strings and produces strings. Internally, it's a wrapper for \str_replace().
     *
     * @param string[]|string $search
     * @param string[]|string $replace
     */
    public static function replace($search, $replace): Mapper
    {
        return new Replace($search, $replace);
    }
    
    /**
     * It works with arrays, strings and \Traversable objects.
     * For strings, it mixes order of chars in string using \str_shuffle().
     * For arrays and traversables, it mixes order of values in array using \shuffle() function.
     */
    public static function shuffle(): Mapper
    {
        return new Shuffle();
    }
    
    /**
     * It works with strings and arrays and reverses order of chars and elements respectively.
     */
    public static function reverse(): Mapper
    {
        return new Reverse();
    }
    
    /**
     * @param array<string|int>|string|int $fields
     * @param mixed|null $orElse
     */
    public static function extract($fields, $orElse = null): Mapper
    {
        return Extract::create($fields, $orElse);
    }
    
    /**
     * @param string|int $field
     * @param MapperReady|callable|iterable|mixed $mapper
     */
    public static function append($field, $mapper): Mapper
    {
        return new Append($field, $mapper);
    }
    
    /**
     * @param string|int $field
     * @param MapperReady|callable|iterable|mixed $mapper
     */
    public static function complete($field, $mapper): Mapper
    {
        return new Complete($field, $mapper);
    }
    
    /**
     * @param mixed $value
     */
    public static function simple($value): Mapper
    {
        return new Simple($value);
    }
    
    /**
     * @param array<string|int>|string|int $fields
     */
    public static function remove($fields): Mapper
    {
        return new Remove($fields);
    }
    
    public static function jsonEncode(?int $flags = null): Mapper
    {
        return new JsonEncode($flags);
    }
    
    public static function jsonDecode(?int $flags = null, bool $associative = true): Mapper
    {
        return new JsonDecode($flags, $associative);
    }
    
    /**
     * @param string|int $field
     * @param string|int|null $key
     */
    public static function moveTo($field, $key = null): Mapper
    {
        return MoveTo::create($field, $key);
    }
    
    /**
     * @param string|int $field
     * @param MapperReady|callable|iterable|mixed $mapper
     */
    public static function mapField($field, $mapper): Mapper
    {
        return new MapField($field, self::getAdapter($mapper));
    }
    
    public static function round(int $precision = 2): Mapper
    {
        return new Round($precision);
    }
    
    public static function tokenize(string $tokens = ' '): Mapper
    {
        return new Tokenize($tokens);
    }
    
    public static function trim(string $chars = " \t\n\r\0\x0B"): Mapper
    {
        return new Trim($chars);
    }
    
    /**
     * @param array<string|int> $keys
     */
    public static function remap(array $keys): Mapper
    {
        return new Remap($keys);
    }
    
    /**
     * @param string|int $field
     */
    public static function fieldValue($field): Mapper
    {
        return new FieldValue($field);
    }
    
    public static function value(): Mapper
    {
        return new Value();
    }
    
    public static function key(): Mapper
    {
        return new Key();
    }
    
    /**
     * @param mixed $variable REFERENCE
     */
    public static function readFrom(&$variable): Mapper
    {
        return new Reference($variable);
    }
    
    /**
     * This is a convenient way to use the \array_column function.
     *
     * @param string|int|null $column
     * @param string|int|null $index
     */
    public static function arrayColumn($column, $index = null): Mapper
    {
        return self::getAdapter(static fn(array $rows): array => \array_column($rows, $column, $index));
    }
    
    public static function increment(int $step = 1): Mapper
    {
        return new Increment($step);
    }
    
    public static function decrement(int $step = 1): Mapper
    {
        return new Increment(-$step);
    }
    
    /**
     * This mapper requiers a \DateTimeInterface object on input and returns string with formatted time value.
     */
    public static function formatTime(string $format = 'Y-m-d H:i:s'): Mapper
    {
        return new FormatTime($format);
    }
    
    /**
     * This mapper reindexes keys numerically in array-values.
     */
    public static function reindexKeys(int $start = 0, int $step = 1): Mapper
    {
        return $start === 0 && $step === 1
            ? new ReindexKeysSimple()
            : new ReindexKeysComplex($start, $step);
    }
    
    /**
     * Change the order of keys in array-like values.
     *
     * @param array<string|int> $keys
     */
    public static function reorderKeys(array $keys): Mapper
    {
        return new ReorderKeys($keys);
    }
    
    public static function byArgs(callable $mapper): Mapper
    {
        return new ByArgs($mapper);
    }
    
    /**
     * Get rid of $length first elements of an array value.
     * Optionally, it can reindex remaining elements numerically.
     */
    public static function skip(int $length, bool $reindex = false): Mapper
    {
        if ($length < 1) {
            throw InvalidParamException::describe('length', $length);
        }
        
        return self::slice($length, null, $reindex);
    }
    
    /**
     * Cut off all elements after first $limit.
     * Optionally, it can reindex remaining elements numerically.
     */
    public static function limit(int $limit, bool $reindex = false): Mapper
    {
        return self::slice(0, $limit, $reindex);
    }
    
    /**
     * A convinient way to extract a slice of an array value.
     */
    public static function slice(int $offset, ?int $length = null, bool $reindex = false): Mapper
    {
        return new Slice($offset, $length, $reindex);
    }
    
    /**
     * @param MapperReady|callable|iterable|mixed $mapper
     */
    public static function forEach($mapper): Mapper
    {
        return new ApplyForEach($mapper);
    }
}