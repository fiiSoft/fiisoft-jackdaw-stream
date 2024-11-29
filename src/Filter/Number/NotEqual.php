<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Number;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\Number\NotEqual\AnyNotEqual;
use FiiSoft\Jackdaw\Filter\Number\NotEqual\BothNotEqual;
use FiiSoft\Jackdaw\Filter\Number\NotEqual\KeyNotEqual;
use FiiSoft\Jackdaw\Filter\Number\NotEqual\ValueNotEqual;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Mode;

abstract class NotEqual extends SingleArg
{
    /**
     * @param float|int $value
     */
    final public static function create(int $mode, $value): self
    {
        switch ($mode) {
            case Check::VALUE:
                return new ValueNotEqual($mode, $value);
            case Check::KEY:
                return new KeyNotEqual($mode, $value);
            case Check::BOTH:
                return new BothNotEqual($mode, $value);
            case Check::ANY:
                return new AnyNotEqual($mode, $value);
            default:
                throw Mode::invalidModeException($mode);
        }
    }
    
    final public function negate(): Filter
    {
        return Equal::create($this->negatedMode(), $this->number);
    }
}