<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Mapper\Internal\FilterAdapter;
use FiiSoft\Jackdaw\Mapper\Internal\PredicateAdapter;
use FiiSoft\Jackdaw\Mapper\Internal\ReducerAdapter;
use FiiSoft\Jackdaw\Mapper\Internal\RegistryAdapter;
use FiiSoft\Jackdaw\Predicate\Predicate;
use FiiSoft\Jackdaw\Reducer\Reducer;
use FiiSoft\Jackdaw\Registry\RegReader;

final class Mappers
{
    /**
     * @param Mapper|Reducer|Predicate|Filter|callable|mixed $mapper
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
                    default:
                        //noop
                }
            }
            
            return self::generic($mapper);
        }
    
        if ($mapper instanceof Reducer) {
            return new ReducerAdapter($mapper);
        }
    
        if ($mapper instanceof Filter) {
            return new FilterAdapter($mapper);
        }
    
        if ($mapper instanceof Predicate) {
            return new PredicateAdapter($mapper);
        }
        
        if ($mapper instanceof RegReader) {
            return new RegistryAdapter($mapper);
        }
        
        return self::simple($mapper);
    }
    
    public static function generic(callable $mapper): Mapper
    {
        return new GenericMapper($mapper);
    }
    
    /**
     * @param array|string|int|null $fields
     */
    public static function toInt($fields = null): Mapper
    {
        return new ToInt($fields);
    }
    
    /**
     * @param array|string|int|null $fields
     */
    public static function toString($fields = null): Mapper
    {
        return new ToString($fields);
    }
    
    /**
     * @param array|string|int|null $fields
     */
    public static function toFloat($fields = null): Mapper
    {
        return new ToFloat($fields);
    }
    
    /**
     * @param array|string|int|null $fields
     */
    public static function toBool($fields = null): Mapper
    {
        return new ToBool($fields);
    }
    
    public static function toArray(bool $appendKey = false): Mapper
    {
        return new ToArray($appendKey);
    }
    
    public static function concat(string $separator = ''): Mapper
    {
        return new Concat($separator);
    }
    
    public static function split(string $separator = ' '): Mapper
    {
        return new Split($separator);
    }
    
    public static function shuffle(): Mapper
    {
        return new Shuffle();
    }
    
    public static function reverse(): Mapper
    {
        return new Reverse();
    }
    
    /**
     * @param array|string|int $fields
     * @param mixed|null $orElse
     */
    public static function extract($fields, $orElse = null): Mapper
    {
        return new Extract($fields, $orElse);
    }
    
    /**
     * @param string|int $field
     * @param Mapper|Reducer|callable|mixed $mapper
     */
    public static function append($field, $mapper): Mapper
    {
        return new Append($field, $mapper);
    }
    
    /**
     * @param string|int $field
     * @param Mapper|Reducer|callable|mixed $mapper
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
     * @param array|string|int $fields
     */
    public static function remove($fields): Mapper
    {
        return new Remove($fields);
    }
    
    public static function jsonEncode(int $flags = 0): Mapper
    {
        return new JsonEncode($flags);
    }
    
    public static function jsonDecode(int $flags = 0, bool $associative = true): Mapper
    {
        return new JsonDecode($flags, $associative);
    }
    
    /**
     * @param string|int $field
     * @param string|int|null $key
     */
    public static function moveTo($field, $key = null): Mapper
    {
        return new MoveTo($field, $key);
    }
    
    /**
     * @param string|int $field
     * @param Mapper|Reducer|callable|mixed $mapper
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
}