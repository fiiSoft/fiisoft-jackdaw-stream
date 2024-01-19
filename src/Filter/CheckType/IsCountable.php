<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\CheckType;

use FiiSoft\Jackdaw\Filter\CheckType\IsCountable\AnyIsCountable;
use FiiSoft\Jackdaw\Filter\CheckType\IsCountable\BothIsCountable;
use FiiSoft\Jackdaw\Filter\CheckType\IsCountable\KeyIsCountable;
use FiiSoft\Jackdaw\Filter\CheckType\IsCountable\ValueIsCountable;
use FiiSoft\Jackdaw\Internal\Check;

abstract class IsCountable extends CheckType
{
    final public static function create(?int $mode): self
    {
        $mode = Check::getMode($mode);
        
        switch ($mode) {
            case Check::VALUE:
                return new ValueIsCountable($mode);
            case Check::KEY:
                return new KeyIsCountable($mode);
            case Check::BOTH:
                return new BothIsCountable($mode);
            default:
                return new AnyIsCountable($mode);
        }
    }
}