<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Discriminator\Exception;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Exception\JackdawException;
use FiiSoft\Jackdaw\Internal\Helper;

final class DiscriminatorExceptionFactory
{
    public static function paramsYesAndNoCannotBeTheSame(): JackdawException
    {
        return InvalidParamException::create('Params yes and no cannot be the same');
    }
    
    public static function invalidParamClassifier(int $numOfArgs): JackdawException
    {
        return Helper::wrongNumOfArgsException('Classifier', $numOfArgs, 1, 2, 0);
    }
}