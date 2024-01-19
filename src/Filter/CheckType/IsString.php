<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\CheckType;

use FiiSoft\Jackdaw\Filter\CheckType\IsString\AnyIsString;
use FiiSoft\Jackdaw\Filter\CheckType\IsString\BothIsString;
use FiiSoft\Jackdaw\Filter\CheckType\IsString\KeyIsString;
use FiiSoft\Jackdaw\Filter\CheckType\IsString\ValueIsString;
use FiiSoft\Jackdaw\Internal\Check;

abstract class IsString extends CheckType
{
    final public static function create(?int $mode): self
    {
        $mode = Check::getMode($mode);
        
        switch ($mode) {
            case Check::VALUE:
                return new ValueIsString($mode);
            case Check::KEY:
                return new KeyIsString($mode);
            case Check::BOTH:
                return new BothIsString($mode);
            default:
                return new AnyIsString($mode);
        }
    }
}