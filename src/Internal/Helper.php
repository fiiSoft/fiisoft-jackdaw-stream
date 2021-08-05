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
}