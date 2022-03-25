<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Mapper\Internal\ReducerAdapter;
use FiiSoft\Jackdaw\Reducer\Reducer;

final class Mappers
{
    /**
     * @param Mapper|Reducer|callable|mixed $mapper
     * @return Mapper
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
                    default:
                        //noop
                }
            }
            
            return self::generic($mapper);
        }
    
        if ($mapper instanceof Reducer) {
            return new ReducerAdapter($mapper);
        }
        
        return self::simple($mapper);
    }
    
    public static function generic(callable $mapper): GenericMapper
    {
        return new GenericMapper($mapper);
    }
    
    /**
     * @param array|string|int|null $fields
     * @return ToInt
     */
    public static function toInt($fields = null): ToInt
    {
        return new ToInt($fields);
    }
    
    /**
     * @param array|string|int|null $fields
     * @return ToString
     */
    public static function toString($fields = null): ToString
    {
        return new ToString($fields);
    }
    
    /**
     * @param array|string|int|null $fields
     * @return ToFloat
     */
    public static function toFloat($fields = null): ToFloat
    {
        return new ToFloat($fields);
    }
    
    /**
     * @param array|string|int|null $fields
     * @return ToBool
     */
    public static function toBool($fields = null): ToBool
    {
        return new ToBool($fields);
    }
    
    public static function toArray(bool $appendKey = false): ToArray
    {
        return new ToArray($appendKey);
    }
    
    public static function concat(string $separator = ''): Concat
    {
        return new Concat($separator);
    }
    
    public static function split(string $separator = ' '): Split
    {
        return new Split($separator);
    }
    
    public static function reverse(): Reverse
    {
        return new Reverse();
    }
    
    /**
     * @param array|string|int $fields
     * @param mixed|null $orElse
     * @return Extract
     */
    public static function extract($fields, $orElse = null): Extract
    {
        return new Extract($fields, $orElse);
    }
    
    /**
     * @param string|int $field
     * @param Mapper|Reducer|callable|mixed $mapper
     * @return Append
     */
    public static function append($field, $mapper): Append
    {
        return new Append($field, $mapper);
    }
    
    /**
     * @param string|int $field
     * @param Mapper|Reducer|callable|mixed $mapper
     * @return Complete
     */
    public static function complete($field, $mapper): Complete
    {
        return new Complete($field, $mapper);
    }
    
    /**
     * @param mixed $value
     * @return Simple
     */
    public static function simple($value): Simple
    {
        return new Simple($value);
    }
    
    /**
     * @param array|string|int $fields
     * @return Remove
     */
    public static function remove($fields): Remove
    {
        return new Remove($fields);
    }
    
    public static function jsonEncode(int $flags = 0): JsonEncode
    {
        return new JsonEncode($flags);
    }
    
    public static function jsonDecode(int $flags = 0, bool $associative = true): JsonDecode
    {
        return new JsonDecode($flags, $associative);
    }
    
    /**
     * @param string|int $field
     * @param string|int|null $key
     * @return MoveTo
     */
    public static function moveTo($field, $key = null): MoveTo
    {
        return new MoveTo($field, $key);
    }
    
    /**
     * @param string|int $field
     * @param Mapper|Reducer|callable|mixed $mapper
     * @return MapField
     */
    public static function mapField($field, $mapper): MapField
    {
        return new MapField($field, self::getAdapter($mapper));
    }
    
    public static function round(int $precision = 2): Round
    {
        return new Round($precision);
    }
    
    public static function tokenize(string $tokens = ' '): Tokenize
    {
        return new Tokenize($tokens);
    }
    
    public static function trim(string $chars = " \t\n\r\0\x0B"): Trim
    {
        return new Trim($chars);
    }
    
    public static function remap(array $keys): Remap
    {
        return new Remap($keys);
    }
}