<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter;

abstract class AbstractLogicFilter implements LogicFilter
{
    protected function __construct()
    {
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
}