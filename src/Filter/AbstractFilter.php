<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter;

use FiiSoft\Jackdaw\Filter\Logic\FilterNOT;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Mode;

abstract class AbstractFilter implements Filter
{
    final public function checkValue(): Filter
    {
        return $this->inMode(Check::VALUE);
    }
    
    final public function checkKey(): Filter
    {
        return $this->inMode(Check::KEY);
    }
    
    final public function checkBoth(): Filter
    {
        return $this->inMode(Check::BOTH);
    }
    
    final public function checkAny(): Filter
    {
        return $this->inMode(Check::ANY);
    }
    
    /**
     * Helper method.
     */
    final protected function createDefaultNOT(bool $direct = false): Filter
    {
        return new FilterNOT($direct ? $this : $this->inMode($this->negatedMode()));
    }
    
    /**
     * Helper method.
     */
    final protected function negatedMode(): ?int
    {
        return Mode::negate($this->getMode());
    }
    
    /**
     * @inheritDoc
     */
    final public function and($filter): Filter
    {
        return Filters::AND($this, $filter);
    }
    
    /**
     * @inheritDoc
     */
    final public function andNot($filter): Filter
    {
        return Filters::AND($this, Filters::NOT($filter));
    }
    
    /**
     * @inheritDoc
     */
    final public function or($filter): Filter
    {
        return Filters::OR($this, $filter);
    }
    
    /**
     * @inheritDoc
     */
    final public function orNot($filter): Filter
    {
        return Filters::OR($this, Filters::NOT($filter));
    }
    
    /**
     * @inheritDoc
     */
    final public function xor($filter): Filter
    {
        return Filters::XOR($this, $filter);
    }
    
    /**
     * @inheritDoc
     */
    final public function xnor($filter): Filter
    {
        return Filters::XNOR($this, $filter);
    }
    
    public function adjust(FilterAdjuster $adjuster): Filter
    {
        return $adjuster->adjust($this);
    }
}