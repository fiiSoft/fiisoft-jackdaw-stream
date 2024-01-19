<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper\Exception;

use FiiSoft\Jackdaw\Exception\JackdawException;
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
    public static function unsupportedValue($value): JackdawException
    {
        return UnsupportedValueException::create(
            'Unsupported '.Helper::typeOfParam($value).' as value in Remove mapper'
        );
    }
    
    public static function invalidParamMapper(int $numOfArgs): JackdawException
    {
        return Helper::wrongNumOfArgsException('Mapper', $numOfArgs, 0, 1, 2);
    }
}