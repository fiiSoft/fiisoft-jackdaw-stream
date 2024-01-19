<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\CheckType;

use FiiSoft\Jackdaw\Filter\CheckType\IsBool\AnyIsBool;
use FiiSoft\Jackdaw\Filter\CheckType\IsBool\BothIsBool;
use FiiSoft\Jackdaw\Filter\CheckType\IsBool\KeyIsBool;
use FiiSoft\Jackdaw\Filter\CheckType\IsBool\ValueIsBool;
use FiiSoft\Jackdaw\Internal\Check;

abstract class IsBool extends CheckType
{
    final public static function create(?int $mode): self
    {
        $mode = Check::getMode($mode);
        
        switch ($mode) {
            case Check::VALUE:
                return new ValueIsBool($mode);
            case Check::KEY:
                return new KeyIsBool($mode);
            case Check::BOTH:
                return new BothIsBool($mode);
            default:
                return new AnyIsBool($mode);
        }
    }
}