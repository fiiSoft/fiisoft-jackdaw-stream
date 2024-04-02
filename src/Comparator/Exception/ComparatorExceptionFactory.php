<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator\Exception;

use FiiSoft\Jackdaw\Exception\ImpossibleSituationException;
use FiiSoft\Jackdaw\Exception\InvalidOperationException;
use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Exception\JackdawException;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Helper;

final class ComparatorExceptionFactory
{
    public static function cannotCompareOnlyValues(object $object): JackdawException
    {
        return ImpossibleSituationException::called('compare', $object);
    }
    
    public static function sortingsCannotBeTheSame(): JackdawException
    {
        return InvalidParamException::create('Sorting specifications cannot be of the same type');
    }
    
    public static function invalidSortingCallable(string $what): JackdawException
    {
        return InvalidParamException::create('Cannot sort by '.$what.' with callable that requires four arguments');
    }
    
    public static function wrongComparisonCallable(int $mode): JackdawException
    {
        return InvalidParamException::create(
            'Cannot compare by '.($mode === Check::VALUE ? 'values' : 'keys')
            .' with callable that requires four arguments'
        );
    }
    
    public static function invalidParamComparator(int $numOfArgs): JackdawException
    {
        return Helper::wrongNumOfArgsException('Comparator', $numOfArgs, 1, 2, 4);
    }
    
    public static function paramFieldsCannotBeEmpty(): JackdawException
    {
        return InvalidParamException::create('Param fields cannot be empty');
    }
    
    public static function paramFieldsIsInvalid(): JackdawException
    {
        return InvalidParamException::create('Each element of array fields have to be a non empty string or integer');
    }
    
    public static function compareAssocIsNotImplemented(): JackdawException
    {
        return InvalidOperationException::create('Sorry, this comparision is not implemented, and never will be');
    }
    
    public static function cannotCompareTwoValues(): JackdawException
    {
        return InvalidOperationException::create('Cannot compare two values because comparator requires 4 arguments');
    }
}