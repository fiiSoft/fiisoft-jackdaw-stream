<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\CheckType;

use FiiSoft\Jackdaw\Filter\CheckType\NotEmpty\AnyNotEmpty;
use FiiSoft\Jackdaw\Filter\CheckType\NotEmpty\BothNotEmpty;
use FiiSoft\Jackdaw\Filter\CheckType\NotEmpty\KeyNotEmpty;
use FiiSoft\Jackdaw\Filter\CheckType\NotEmpty\ValueNotEmpty;
use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Mode;

abstract class NotEmpty extends CheckType
{
    final public static function create(?int $mode): self
    {
        $mode = Mode::get($mode);
        
        switch ($mode) {
            case Check::VALUE:
                return new ValueNotEmpty($mode);
            case Check::KEY:
                return new KeyNotEmpty($mode);
            case Check::BOTH:
                return new BothNotEmpty($mode);
            default:
                return new AnyNotEmpty($mode);
        }
    }
    
    final public function negate(): Filter
    {
        return IsEmpty::create($this->negatedMode());
    }
}