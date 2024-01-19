<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Registry\Exception;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Exception\JackdawException;
use FiiSoft\Jackdaw\Exception\UnsupportedValueException;

final class RegistryExceptionFactory
{
    public static function cannotSetValue(): JackdawException
    {
        return UnsupportedValueException::create('Registry writer requires null or tuple [key,value] to set directly');
    }
    
    public static function parametersValueAndKeyCannotBeTheSame(): JackdawException
    {
        return InvalidParamException::create('Parameters value and key cannot be the same');
    }
}