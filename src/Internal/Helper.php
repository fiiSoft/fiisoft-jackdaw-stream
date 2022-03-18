<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal;

final class Helper
{
    public static function getNumOfArgs(callable $callable): int
    {
        return (new \ReflectionFunction($callable))->getNumberOfRequiredParameters();
    }
    
    public static function invalidParamException(string $name, $param): \InvalidArgumentException
    {
        return new \InvalidArgumentException(
            'Invalid param '.$name.' - it cannot be '.self::typeOfParam($param)
        );
    }
    
    public static function typeOfParam($param): string
    {
        return \is_object($param) ? 'object of type '.\get_class($param) : \gettype($param);
    }
    
    public static function wrongNumOfArgsException(string $name, int $current, ...$alowed): \LogicException
    {
        if (empty($alowed)) {
            $alowed[] = 0;
        }
        
        $last = \array_pop($alowed);
        $message = $name.' have to accept ';
        
        if (empty($alowed)) {
            $message .= $last;
        } else {
            $message .= \implode(', ', $alowed).' or '.$last;
        }
    
        $message .= ' arguments, but requires '.$current;
        
        return new \LogicException($message);
    }
    
    public static function isFieldValid($field): bool
    {
        return \is_string($field) && $field !== '' || \is_int($field);
    }
    
    /**
     * @param mixed $value
     * @return string
     */
    public static function describe($value): string
    {
        if ($value === null) {
            return 'NULL';
        }
        
        if (\is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }
        
        if (\is_numeric($value)) {
            return (string) $value;
        }
        
        if (\is_string($value)) {
            return \mb_strlen($value) > 50 ? \mb_substr($value, 0, 47).'...' : $value;
        }
        
        if (\is_array($value)) {
            $desc = 'array of length: '.\count($value);
            
            $json = \json_encode($value);
            if ($json !== false) {
                if (\mb_strlen($json) > 50) {
                    $desc .= ' '.\mb_substr($json, 0, 47).'...';
                } else {
                    $desc .= ' '.$json;
                }
            }
            
            return $desc;
        }
        
        if (\is_object($value)) {
            return 'object of class: '.\get_class($value);
        }
        
        return \gettype($value);
    }
}