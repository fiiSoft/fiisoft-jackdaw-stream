<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Number;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\Number\LessOrEqual\AnyLessOrEqual;
use FiiSoft\Jackdaw\Filter\Number\LessOrEqual\BothLessOrEqual;
use FiiSoft\Jackdaw\Filter\Number\LessOrEqual\KeyLessOrEqual;
use FiiSoft\Jackdaw\Filter\Number\LessOrEqual\ValueLessOrEqual;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Mode;

abstract class LessOrEqual extends SingleArg
{
    /**
     * @param float|int $value
     */
    final public static function create(int $mode, $value): self
    {
        switch ($mode) {
            case Check::VALUE:
                return new ValueLessOrEqual($mode, $value);
            case Check::KEY:
                return new KeyLessOrEqual($mode, $value);
            case Check::BOTH:
                return new BothLessOrEqual($mode, $value);
            case Check::ANY:
                return new AnyLessOrEqual($mode, $value);
            default:
                throw Mode::invalidModeException($mode);
        }
    }
    
    final public function negate(): Filter
    {
        return GreaterThan::create($this->negatedMode(), $this->number);
    }
}