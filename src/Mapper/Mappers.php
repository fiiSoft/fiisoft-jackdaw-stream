<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

final class Mappers
{
    /**
     * @param Mapper|callable|mixed $mapper
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
                    case 'intval': return self::toInt();
                    case 'strval': return self::toString();
                    case 'floatval': return self::toFloat();
                    case 'boolval': return self::toBool();
                    case 'implode': return self::concat();
                    case 'explode': return self::split();
                    case 'array_reverse': return self::reverse();
                    case 'json_encode': return self::jsonEncode();
                    case 'json_decode': return self::jsonDecode();
                    default:
                        //noop
                }
            }
            
            return self::generic($mapper);
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
    
    public static function toString(): ToString
    {
        return new ToString();
    }
    
    /**
     * @param array|string|int|null $fields
     * @return ToFloat
     */
    public static function toFloat($fields = null): ToFloat
    {
        return new ToFloat($fields);
    }
    
    public static function toBool(): ToBool
    {
        return new ToBool();
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
     * @param Mapper|callable|mixed $mapper
     * @return Append
     */
    public static function append($field, $mapper): Append
    {
        return new Append($field, $mapper);
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
}