<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\CheckType;

use FiiSoft\Jackdaw\Filter\CheckType\IsFloat\AnyIsFloat;
use FiiSoft\Jackdaw\Filter\CheckType\IsFloat\BothIsFloat;
use FiiSoft\Jackdaw\Filter\CheckType\IsFloat\KeyIsFloat;
use FiiSoft\Jackdaw\Filter\CheckType\IsFloat\ValueIsFloat;
use FiiSoft\Jackdaw\Internal\Check;

abstract class IsFloat extends CheckType
{
    final public static function create(?int $mode): self
    {
        $mode = Check::getMode($mode);
        
        switch ($mode) {
            case Check::VALUE:
                return new ValueIsFloat($mode);
            case Check::KEY:
                return new KeyIsFloat($mode);
            case Check::BOTH:
                return new BothIsFloat($mode);
            default:
                return new AnyIsFloat($mode);
        }
    }
}