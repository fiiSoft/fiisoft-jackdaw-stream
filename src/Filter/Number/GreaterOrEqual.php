<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Number;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\Number\GreaterOrEqual\AnyGreaterOrEqual;
use FiiSoft\Jackdaw\Filter\Number\GreaterOrEqual\BothGreaterOrEqual;
use FiiSoft\Jackdaw\Filter\Number\GreaterOrEqual\KeyGreaterOrEqual;
use FiiSoft\Jackdaw\Filter\Number\GreaterOrEqual\ValueGreaterOrEqual;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Mode;

abstract class GreaterOrEqual extends SingleArg
{
    /**
     * @param float|int $value
     */
    final public static function create(int $mode, $value): self
    {
        switch ($mode) {
            case Check::VALUE:
                return new ValueGreaterOrEqual($mode, $value);
            case Check::KEY:
                return new KeyGreaterOrEqual($mode, $value);
            case Check::BOTH:
                return new BothGreaterOrEqual($mode, $value);
            case Check::ANY:
                return new AnyGreaterOrEqual($mode, $value);
            default:
                throw Mode::invalidModeException($mode);
        }
    }
    
    final public function negate(): Filter
    {
        return LessThan::create($this->negatedMode(), $this->number);
    }
}