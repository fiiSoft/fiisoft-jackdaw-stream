<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Exception\JackdawException;

final class Mode
{
    public static function get(?int $mode): int
    {
        if ($mode === null) {
            return Check::VALUE;
        }
        
        if ($mode === Check::VALUE || $mode === Check::KEY || $mode === Check::BOTH || $mode === Check::ANY) {
            return $mode;
        }
        
        throw self::invalidModeException($mode);
    }
    
    public static function invalidModeException(?int $mode): JackdawException
    {
        return InvalidParamException::describe('mode', $mode);
    }
}