<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\CheckType;

use FiiSoft\Jackdaw\Filter\CheckType\IsNumeric\AnyIsNumeric;
use FiiSoft\Jackdaw\Filter\CheckType\IsNumeric\BothIsNumeric;
use FiiSoft\Jackdaw\Filter\CheckType\IsNumeric\KeyIsNumeric;
use FiiSoft\Jackdaw\Filter\CheckType\IsNumeric\ValueIsNumeric;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Mode;

abstract class IsNumeric extends CheckType
{
    final public static function create(?int $mode): self
    {
        $mode = Mode::get($mode);
        
        switch ($mode) {
            case Check::VALUE:
                return new ValueIsNumeric($mode);
            case Check::KEY:
                return new KeyIsNumeric($mode);
            case Check::BOTH:
                return new BothIsNumeric($mode);
            default:
                return new AnyIsNumeric($mode);
        }
    }
}