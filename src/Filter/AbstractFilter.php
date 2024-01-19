<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter;

use FiiSoft\Jackdaw\Filter\Logic\FilterNOT;
use FiiSoft\Jackdaw\Internal\Check;

abstract class AbstractFilter implements Filter
{
    protected function __construct()
    {
    }
    
    final protected function createDefaultNOT(): Filter
    {
        return new FilterNOT($this->inMode($this->negatedMode()));
    }
    
    final protected function negatedMode(): ?int
    {
        $mode = $this->getMode();
        
        if ($mode === Check::BOTH) {
            $mode = Check::ANY;
        } elseif ($mode === Check::ANY) {
            $mode = Check::BOTH;
        }
        
        return $mode;
    }
    
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
        return Filters::AND($this, $filter->negate());
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
        return Filters::OR($this, $filter->negate());
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
}