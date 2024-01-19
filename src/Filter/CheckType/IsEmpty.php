<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\CheckType;

use FiiSoft\Jackdaw\Filter\CheckType\IsEmpty\AnyIsEmpty;
use FiiSoft\Jackdaw\Filter\CheckType\IsEmpty\BothIsEmpty;
use FiiSoft\Jackdaw\Filter\CheckType\IsEmpty\KeyIsEmpty;
use FiiSoft\Jackdaw\Filter\CheckType\IsEmpty\ValueIsEmpty;
use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Internal\Check;

abstract class IsEmpty extends CheckType
{
    final public static function create(?int $mode): CheckType
    {
        $mode = Check::getMode($mode);
        
        switch ($mode) {
            case Check::VALUE:
                return new ValueIsEmpty($mode);
            case Check::KEY:
                return new KeyIsEmpty($mode);
            case Check::BOTH:
                return new BothIsEmpty($mode);
            default:
                return new AnyIsEmpty($mode);
        }
    }
    
    final public function negate(): Filter
    {
        return NotEmpty::create($this->negatedMode());
    }
}