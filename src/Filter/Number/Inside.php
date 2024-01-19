<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Number;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\IdleFilter;
use FiiSoft\Jackdaw\Filter\Number\Inside\AnyInside;
use FiiSoft\Jackdaw\Filter\Number\Inside\BothInside;
use FiiSoft\Jackdaw\Filter\Number\Inside\KeyInside;
use FiiSoft\Jackdaw\Filter\Number\Inside\ValueInside;
use FiiSoft\Jackdaw\Internal\Check;

abstract class Inside extends TwoArgs
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
    private static function createFilter(int $mode, $lower, $higher): self
    {
        switch ($mode) {
            case Check::VALUE:
                return new ValueInside($mode, $lower, $higher);
            case Check::KEY:
                return new KeyInside($mode, $lower, $higher);
            case Check::BOTH:
                return new BothInside($mode, $lower, $higher);
            case Check::ANY:
                return new AnyInside($mode, $lower, $higher);
            default:
                throw Check::invalidModeException($mode);
        }
    }
    
    private function optimise(): Filter
    {
        return $this->lower == $this->higher ? IdleFilter::false($this->mode) : $this;
    }
    
    final public function negate(): Filter
    {
        return NotInside::create($this->negatedMode(), $this->lower, $this->higher);
    }
}