<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\CheckType;

use FiiSoft\Jackdaw\Filter\CheckType\IsNull\AnyIsNull;
use FiiSoft\Jackdaw\Filter\CheckType\IsNull\BothIsNull;
use FiiSoft\Jackdaw\Filter\CheckType\IsNull\KeyIsNull;
use FiiSoft\Jackdaw\Filter\CheckType\IsNull\ValueIsNull;
use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Mode;

abstract class IsNull extends CheckType
{
    final public static function create(?int $mode): self
    {
        $mode = Mode::get($mode);
        
        switch ($mode) {
            case Check::VALUE:
                return new ValueIsNull($mode);
            case Check::KEY:
                return new KeyIsNull($mode);
            case Check::BOTH:
                return new BothIsNull($mode);
            default:
                return new AnyIsNull($mode);
        }
    }
    
    final public function negate(): Filter
    {
        return NotNull::create($this->negatedMode());
    }
}