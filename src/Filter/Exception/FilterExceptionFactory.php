<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Exception;

use FiiSoft\Jackdaw\Exception\InvalidOperationException;
use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Exception\JackdawException;
use FiiSoft\Jackdaw\Exception\UnsupportedValueException;
use FiiSoft\Jackdaw\Internal\Helper;

final class FilterExceptionFactory
{
    /**
     * @param mixed $time
     */
    public static function invalidTimeValue($time): JackdawException
    {
        return UnsupportedValueException::create(
            'Cannot compare '.Helper::typeOfParam($time).' with a DateTimeInterface object'
        );
    }
    
    public static function paramFromIsGreaterThanUntil(): JackdawException
    {
        return InvalidParamException::create('Param from is greater than param until');
    }
    
    public static function paramFieldsCannotBeEmpty(): JackdawException
    {
        return InvalidParamException::create('Param fields cannot be empty');
    }
    
    public static function modeNotSupportedYet(int $mode): JackdawException
    {
        return InvalidOperationException::create('Mode '.$mode.' is not currently supported by filter OnlyWith');
    }
    
    public static function paramLowerCannotBeGreaterThanHigher(): JackdawException
    {
        return InvalidParamException::create('Param lower is greater than param higher');
    }
    
    public static function invalidParamFilter(int $numOfArgs): JackdawException
    {
        return Helper::wrongNumOfArgsException('Filter', $numOfArgs, 0, 1, 2);
    }
}