<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter;

use FiiSoft\Jackdaw\Filter\Logic\FilterNOT;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Mode;

abstract class AbstractFilter extends AbstractLogicFilter implements Filter
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
}