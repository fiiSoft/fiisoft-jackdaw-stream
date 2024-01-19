<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Number;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\Number\Between\AnyBetween;
use FiiSoft\Jackdaw\Filter\Number\Between\BothBetween;
use FiiSoft\Jackdaw\Filter\Number\Between\KeyBetween;
use FiiSoft\Jackdaw\Filter\Number\Between\ValueBetween;
use FiiSoft\Jackdaw\Internal\Check;

abstract class Between extends TwoArgs
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
    private static function createFilter(int $mode, $lower, $higher): Between
    {
        switch ($mode) {
            case Check::VALUE:
                return new ValueBetween($mode, $lower, $higher);
            case Check::KEY:
                return new KeyBetween($mode, $lower, $higher);
            case Check::BOTH:
                return new BothBetween($mode, $lower, $higher);
            case Check::ANY:
                return new AnyBetween($mode, $lower, $higher);
            default:
                throw Check::invalidModeException($mode);
        }
    }
    
    private function optimise(): Filter
    {
        return $this->lower == $this->higher ? Equal::create($this->mode, $this->lower) : $this;
    }
    
    final public function negate(): Filter
    {
        return Outside::create($this->negatedMode(), $this->lower, $this->higher);
    }
}