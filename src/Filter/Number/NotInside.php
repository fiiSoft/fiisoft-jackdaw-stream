<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Number;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\IdleFilter;
use FiiSoft\Jackdaw\Filter\Number\NotInside\AnyNotInside;
use FiiSoft\Jackdaw\Filter\Number\NotInside\BothNotInside;
use FiiSoft\Jackdaw\Filter\Number\NotInside\KeyNotInside;
use FiiSoft\Jackdaw\Filter\Number\NotInside\ValueNotInside;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Mode;

abstract class NotInside extends TwoArgs
{
    /**
     * @param float|int $lower
     * @param float|int $higher
     */
    final public static function create(int $mode, $lower, $higher): Filter
    {
        return self::createFilter($mode, $lower, $higher)->optimise();
    }
    
    /**
     * @param float|int $lower
     * @param float|int $higher
     */
    private static function createFilter(int $mode, $lower, $higher): NotInside
    {
        switch ($mode) {
            case Check::VALUE:
                return new ValueNotInside($mode, $lower, $higher);
            case Check::KEY:
                return new KeyNotInside($mode, $lower, $higher);
            case Check::BOTH:
                return new BothNotInside($mode, $lower, $higher);
            case Check::ANY:
                return new AnyNotInside($mode, $lower, $higher);
            default:
                throw Mode::invalidModeException($mode);
        }
    }
    
    private function optimise(): Filter
    {
        return $this->lower == $this->higher ? IdleFilter::true($this->mode) : $this;
    }
    
    final public function negate(): Filter
    {
        return Inside::create($this->negatedMode(), $this->lower, $this->higher);
    }
}