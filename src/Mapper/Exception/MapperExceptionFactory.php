<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper\Exception;

use FiiSoft\Jackdaw\Exception\JackdawException;
use FiiSoft\Jackdaw\Exception\OperationFailedException;
use FiiSoft\Jackdaw\Exception\UnsupportedValueException;
use FiiSoft\Jackdaw\Internal\Helper;

final class MapperExceptionFactory
{
    /**
     * @param mixed $value
     */
    public static function unableToRoundValue($value): JackdawException
    {
        return UnsupportedValueException::create('Unable to round non-number value '.Helper::typeOfParam($value));
    }
    
    /**
     * @param mixed $value
     */
    public static function unableToReverse($value): JackdawException
    {
        return UnsupportedValueException::create('Unable to reverse '.Helper::typeOfParam($value));
    }
    
    /**
     * @param mixed $value
     */
    public static function cannotRemoveFieldFrom($value): JackdawException
    {
        return UnsupportedValueException::create(
            'Unsupported '.Helper::typeOfParam($value).' as value in Remove mapper'
        );
    }
    
    public static function invalidParamMapper(int $numOfArgs): JackdawException
    {
        return Helper::wrongNumOfArgsException('Mapper', $numOfArgs, 0, 1, 2);
    }
    
    /**
     * @param mixed $value
     */
    public static function cannotCreateTimeObjectFrom($value): JackdawException
    {
        return UnsupportedValueException::create(
            'Cannot create \DateTimeImmutable object from '.Helper::typeOfParam($value)
        );
    }
    
    public static function cannotCreateTimeObjectFromString(string $time, string $format): JackdawException
    {
        return OperationFailedException::create(
            'Faild to create object \DateTimeImmutable from string '.$time.' in format '.$format
        );
    }
    
    /**
     * @param \DateTimeInterface|string|int $time
     */
    public static function cannotCreateTimeObjectWithTimeZone($time, \DateTimeZone $timeZone): JackdawException
    {
        $message = 'Cannot set or change time zone '.$timeZone->getName();
        
        if ($time instanceof \DateTimeInterface) {
            $message .= ' on '.\get_class($time).' object '.$time->format('c');
        } else {
            $message .= ' from '.Helper::describe($time);
        }
        
        return OperationFailedException::create($message);
    }
}