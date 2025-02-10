<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Exception;

use FiiSoft\Jackdaw\Exception\InvalidOperationException;
use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Exception\JackdawException;
use FiiSoft\Jackdaw\Exception\UnsupportedValueException;
use FiiSoft\Jackdaw\Internal\Helper;

final class OperationExceptionFactory
{
    public static function cannotInverseOperation(): JackdawException
    {
        return InvalidOperationException::create('Cannot create inversed operation');
    }
    
    /**
     * @param mixed $classifier
     */
    public static function handlerIsNotDefined($classifier): JackdawException
    {
        return UnsupportedValueException::create(
            'There is no handler defined for classifier '.Helper::describe($classifier)
        );
    }
    
    /**
     * @param mixed $classifier
     */
    public static function mapperIsNotDefined($classifier): JackdawException
    {
        return UnsupportedValueException::create(
            'There is no mapper defined for classifier '.Helper::describe($classifier)
        );
    }
    
    public static function invalidKeyValueMapper(int $numOfArgs): JackdawException
    {
        return Helper::wrongNumOfArgsException('KeyValue mapper', $numOfArgs, 0, 1, 2);
    }
    
    public static function wrongTypeOfKeyValueMapper(): JackdawException
    {
        return InvalidParamException::create('KeyValue mapper must have declared array as its return type');
    }
    
    public static function invalidComparator(): JackdawException
    {
        return InvalidParamException::create('FullAssocChecker can work only with four-argument callable');
    }
}