<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Number;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\Number\Outside\AnyOutside;
use FiiSoft\Jackdaw\Filter\Number\Outside\BothOutside;
use FiiSoft\Jackdaw\Filter\Number\Outside\KeyOutside;
use FiiSoft\Jackdaw\Filter\Number\Outside\ValueOutside;
use FiiSoft\Jackdaw\Internal\Check;

abstract class Outside extends TwoArgs
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
    private static function createFilter(int $mode, $lower, $higher): Outside
    {
        switch ($mode) {
            case Check::VALUE:
                return new ValueOutside($mode, $lower, $higher);
            case Check::KEY:
                return new KeyOutside($mode, $lower, $higher);
            case Check::BOTH:
                return new BothOutside($mode, $lower, $higher);
            case Check::ANY:
                return new AnyOutside($mode, $lower, $higher);
            default:
                throw Check::invalidModeException($mode);
        }
    }
    
    private function optimise(): Filter
    {
        return $this->lower == $this->higher ? NotEqual::create($this->mode, $this->lower) : $this;
    }
    
    final public function negate(): Filter
    {
        return Between::create($this->negatedMode(), $this->lower, $this->higher);
    }
}