<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Reducer\Exception;

use FiiSoft\Jackdaw\Exception\JackdawException;
use FiiSoft\Jackdaw\Internal\Helper;

final class ReducerExceptionFactory
{
    public static function invalidParamReducer(int $numOfArgs): JackdawException
    {
        return Helper::wrongNumOfArgsException('Reducer', $numOfArgs, 2);
    }
}