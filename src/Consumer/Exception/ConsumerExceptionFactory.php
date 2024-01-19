<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Consumer\Exception;

use FiiSoft\Jackdaw\Exception\JackdawException;
use FiiSoft\Jackdaw\Internal\Helper;

final class ConsumerExceptionFactory
{
    public static function invalidParamConsumer(int $numOfArgs): JackdawException
    {
        return Helper::wrongNumOfArgsException('Consumer', $numOfArgs, 0, 1, 2);
    }
}