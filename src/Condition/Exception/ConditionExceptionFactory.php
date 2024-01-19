<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Condition\Exception;

use FiiSoft\Jackdaw\Exception\JackdawException;
use FiiSoft\Jackdaw\Internal\Helper;

final class ConditionExceptionFactory
{
    public static function invalidParamCondition(int $numOfArgs): JackdawException
    {
        return Helper::wrongNumOfArgsException('Condition', $numOfArgs, 1, 2, 0);
    }
}