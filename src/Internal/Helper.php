<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Exception\JackdawException;

final class Helper
{
    private const BUILD_IN_COMPARATORS = ['strcasecmp', 'strcmp', 'strnatcasecmp', 'strnatcmp'];
    
    public static function getNumOfArgs(callable $callable): int
    {
        return self::getFuncRefl($callable)->getNumberOfRequiredParameters();
    }
    
    public static function isDeclaredReturnTypeArray(callable $callable): bool
    {
        return self::isReturnType($callable, 'array');
    }
    
    public static function isDeclaredReturnTypeInt(callable $callable): bool
    {
        return self::isReturnType($callable, 'int');
    }
    
    private static function isReturnType(callable $callable, string $type): bool
    {
        $refl = self::getFuncRefl($callable);
        
        if ($refl->hasReturnType()) {
            $returnType = $refl->getReturnType();
            if ($returnType instanceof \ReflectionNamedType) {
                return $returnType->getName() === $type;
            }
        } elseif ($type === 'int' && \is_string($callable) && $refl->isInternal()) {
            return \in_array(\ltrim($callable, '\\'), self::BUILD_IN_COMPARATORS, true);
        }
        
        return false;
    }
    
    private static function getFuncRefl(callable $callable): \ReflectionFunctionAbstract
    {
        return \is_array($callable)
            ? (new \ReflectionClass($callable[0]))->getMethod($callable[1])
            : new \ReflectionFunction($callable);
    }
    
    /**
     * @param mixed $param
     */
    public static function typeOfParam($param): string
    {
        return \is_object($param) ? 'object of type '.\get_class($param) : \gettype($param);
    }

    
    public static function wrongNumOfArgsException(string $name, int $current, int ...$alowed): JackdawException
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
        
        return InvalidParamException::create($message);
    }
    
    /**
     * @param array<string|int>|string|int $fields
     */
    public static function areFieldsValid($fields): bool
    {
        if (\is_array($fields)) {
            if (empty($fields)) {
                return false;
            }
        
            foreach ($fields as $field) {
                if (!self::isFieldValid($field)) {
                    return false;
                }
            }
        
            return true;
        }
    
        return self::isFieldValid($fields);
    }
    
    /**
     * @param string|int $field
     * @throws JackdawException
     * @return string|int
     */
    public static function validField($field, string $name)
    {
        if (self::isFieldValid($field)) {
            return $field;
        }
        
        throw InvalidParamException::describe($name, $field);
    }
    
    /**
     * @param string|int $field
     */
    public static function isFieldValid($field): bool
    {
        return \is_string($field) && $field !== '' || \is_int($field);
    }
    
    /**
     * @param mixed $value
     */
    public static function describe($value): string
    {
        if ($value === null) {
            return 'NULL';
        }
        
        if (\is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }
        
        if (\is_int($value)) {
            return 'int '.$value;
        }
        
        if (\is_float($value)) {
            return 'float '.$value;
        }
        
        if (\is_numeric($value)) {
            return 'numeric '.$value;
        }
        
        if (\is_string($value)) {
            if (\mb_strlen($value) > 50) {
                $value = \trim(\mb_substr($value, 0, 47)).'...';
            }
            return \trim('string '.$value);
        }
        
        if (\is_array($value)) {
            $desc = 'array of length '.\count($value);
            
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
            return 'object of class '.\get_class($value);
        }
        
        return \gettype($value);
    }
    
    /**
     * @param iterable<mixed, mixed> $iterator
     */
    public static function createItemProducer(Item $item, iterable $iterator): \Iterator
    {
        return (static function () use ($iterator, $item) {
            foreach ($iterator as $item->key => $item->value) {
                yield;
            }
        })();
    }
    
    public static function jsonFlags(?int $flags = null): int
    {
        return ($flags ?? \JSON_PRESERVE_ZERO_FRACTION) | \JSON_THROW_ON_ERROR;
    }
}