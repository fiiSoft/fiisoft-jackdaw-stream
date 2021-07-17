<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal;

final class Check
{
    const VALUE = 1;
    const KEY = 2;
    const BOTH = 3;
    const ANY = 4;
    
    public static function getMode(int $mode): int
    {
        if ($mode === self::VALUE || $mode === self::KEY || $mode === self::BOTH || $mode === self::ANY) {
            return $mode;
        }
        
        throw new \InvalidArgumentException('Invalid param mode');
    }
}