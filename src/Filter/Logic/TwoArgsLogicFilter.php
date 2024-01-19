<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Logic;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\Filters;

abstract class TwoArgsLogicFilter extends LogicFilter
{
    protected Filter $first, $second;
    
    /**
     * @param Filter|callable|mixed $first
     * @param Filter|callable|mixed $second
     */
    abstract protected static function create($first, $second, ?int $mode = null): Filter;
    
    /**
     * @param Filter|callable|mixed $first
     * @param Filter|callable|mixed $second
     */
    protected function __construct($first, $second, ?int $mode = null)
    {
        parent::__construct();
        
        $this->first = Filters::getAdapter($first, $mode);
        $this->second = Filters::getAdapter($second, $mode);
    }
    
    final public function inMode(?int $mode): Filter
    {
        return $mode !== null && $mode !== $this->getMode()
            ? static::create($this->first, $this->second, $mode)
            : $this;
    }
}