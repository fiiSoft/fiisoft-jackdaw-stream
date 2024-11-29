<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\CheckType;

use FiiSoft\Jackdaw\Filter\CheckType\NotNull\AnyNotNull;
use FiiSoft\Jackdaw\Filter\CheckType\NotNull\BothNotNull;
use FiiSoft\Jackdaw\Filter\CheckType\NotNull\KeyNotNull;
use FiiSoft\Jackdaw\Filter\CheckType\NotNull\ValueNotNull;
use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Mode;

abstract class NotNull extends CheckType
{
    final public static function create(?int $mode): self
    {
        $mode = Mode::get($mode);
        
        switch ($mode) {
            case Check::VALUE:
                return new ValueNotNull($mode);
            case Check::KEY:
                return new KeyNotNull($mode);
            case Check::BOTH:
                return new BothNotNull($mode);
            default:
                return new AnyNotNull($mode);
        }
    }
    
    final public function negate(): Filter
    {
        return IsNull::create($this->negatedMode());
    }
}