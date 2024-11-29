<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\CheckType;

use FiiSoft\Jackdaw\Filter\CheckType\IsInt\AnyIsInt;
use FiiSoft\Jackdaw\Filter\CheckType\IsInt\BothIsInt;
use FiiSoft\Jackdaw\Filter\CheckType\IsInt\KeyIsInt;
use FiiSoft\Jackdaw\Filter\CheckType\IsInt\ValueIsInt;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Mode;

abstract class IsInt extends CheckType
{
    final public static function create(?int $mode): self
    {
        $mode = Mode::get($mode);
        
        switch ($mode) {
            case Check::VALUE:
                return new ValueIsInt($mode);
            case Check::KEY:
                return new KeyIsInt($mode);
            case Check::BOTH:
                return new BothIsInt($mode);
            default:
                return new AnyIsInt($mode);
        }
    }
}