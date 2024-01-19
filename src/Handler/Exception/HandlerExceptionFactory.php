<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Handler\Exception;

use FiiSoft\Jackdaw\Exception\JackdawException;
use FiiSoft\Jackdaw\Internal\Helper;

final class HandlerExceptionFactory
{
    public static function invalidParamErrorHandler(int $numOfArgs): JackdawException
    {
        return Helper::wrongNumOfArgsException('ErrorHandler', $numOfArgs, 0, 1, 2, 3);
    }
}