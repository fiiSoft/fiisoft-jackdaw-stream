<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Number;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\Number\LessThan\AnyLessThan;
use FiiSoft\Jackdaw\Filter\Number\LessThan\BothLessThan;
use FiiSoft\Jackdaw\Filter\Number\LessThan\KeyLessThan;
use FiiSoft\Jackdaw\Filter\Number\LessThan\ValueLessThan;
use FiiSoft\Jackdaw\Internal\Check;

abstract class LessThan extends SingleArg
{
    /**
     * @param float|int $value
     */
    final public static function create(int $mode, $value): self
    {
        switch ($mode) {
            case Check::VALUE:
                return new ValueLessThan($mode, $value);
            case Check::KEY:
                return new KeyLessThan($mode, $value);
            case Check::BOTH:
                return new BothLessThan($mode, $value);
            case Check::ANY:
                return new AnyLessThan($mode, $value);
            default:
                throw Check::invalidModeException($mode);
        }
    }
    
    final public function negate(): Filter
    {
        return GreaterOrEqual::create($this->negatedMode(), $this->number);
    }
}