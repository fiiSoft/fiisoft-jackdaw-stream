<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Exception\JackdawException;

final class Check
{
    public const VALUE = 1;
    public const KEY = 2;
    public const BOTH = 3;
    public const ANY = 4;
    
    public static function getMode(?int $mode): int
    {
        if ($mode === null) {
            return self::VALUE;
        }
        
        if ($mode === self::VALUE || $mode === self::KEY || $mode === self::BOTH || $mode === self::ANY) {
            return $mode;
        }
        
        throw self::invalidModeException($mode);
    }
    
    public static function invalidModeException(?int $mode): JackdawException
    {
        return InvalidParamException::describe('mode', $mode);
    }
}