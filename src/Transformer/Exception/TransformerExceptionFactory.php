<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Transformer\Exception;

use FiiSoft\Jackdaw\Exception\JackdawException;
use FiiSoft\Jackdaw\Internal\Helper;

final class TransformerExceptionFactory
{
    public static function invalidParamTransformer(int $numOfArgs): JackdawException
    {
        return Helper::wrongNumOfArgsException('Transformer', $numOfArgs, 1, 2);
    }
}