<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Number;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\Number\GreaterThan\AnyGreaterThan;
use FiiSoft\Jackdaw\Filter\Number\GreaterThan\BothGreaterThan;
use FiiSoft\Jackdaw\Filter\Number\GreaterThan\KeyGreaterThan;
use FiiSoft\Jackdaw\Filter\Number\GreaterThan\ValueGreaterThan;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Mode;

abstract class GreaterThan extends SingleArg
{
    /**
     * @param float|int $value
     */
    final public static function create(int $mode, $value): self
    {
        switch ($mode) {
            case Check::VALUE:
                return new ValueGreaterThan($mode, $value);
            case Check::KEY:
                return new KeyGreaterThan($mode, $value);
            case Check::BOTH:
                return new BothGreaterThan($mode, $value);
            case Check::ANY:
                return new AnyGreaterThan($mode, $value);
            default:
                throw Mode::invalidModeException($mode);
        }
    }
    
    final public function negate(): Filter
    {
        return LessOrEqual::create($this->negatedMode(), $this->number);
    }
}