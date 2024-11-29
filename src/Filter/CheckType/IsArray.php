<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\CheckType;

use FiiSoft\Jackdaw\Filter\CheckType\IsArray\AnyIsArray;
use FiiSoft\Jackdaw\Filter\CheckType\IsArray\BothIsArray;
use FiiSoft\Jackdaw\Filter\CheckType\IsArray\KeyIsArray;
use FiiSoft\Jackdaw\Filter\CheckType\IsArray\ValueIsArray;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Mode;

abstract class IsArray extends CheckType
{
    final public static function create(?int $mode): self
    {
        $mode = Mode::get($mode);
        
        switch ($mode) {
            case Check::VALUE:
                return new ValueIsArray($mode);
            case Check::KEY:
                return new KeyIsArray($mode);
            case Check::BOTH:
                return new BothIsArray($mode);
            default:
                return new AnyIsArray($mode);
        }
    }
}