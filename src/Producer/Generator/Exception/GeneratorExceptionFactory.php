<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Generator\Exception;

use FiiSoft\Jackdaw\Exception\InvalidOperationException;
use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Exception\JackdawException;

final class GeneratorExceptionFactory
{
    public static function invalidParamLevel(int $level, int $maxLevel): JackdawException
    {
        return InvalidParamException::create('Invalid param level, must be 0...'.$maxLevel.' but is '.$level);
    }
    
    public static function cannotDecreaseLevel(): JackdawException
    {
        return InvalidOperationException::create('Cannot decrease level');
    }
    
    public static function maxCannotBeLessThanOrEqualToMin(): JackdawException
    {
        return InvalidParamException::create('Max cannot be less than or equal to min');
    }
    
    public static function maxLengthCannotBeLessThanMinLength(): JackdawException
    {
        return InvalidParamException::create('Max length cannot be less than min length');
    }
}