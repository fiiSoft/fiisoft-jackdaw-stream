<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Logic;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\FilterReady;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Filter\Logic\Conditional\ConditionalNotFilter;
use FiiSoft\Jackdaw\Filter\Logic\Conditional\ConditionalYesFilter;

abstract class ConditionalFilter extends BaseLogicFilter
{
    protected Filter $condition, $filter;
    
    /**
     * @param FilterReady|callable|mixed $condition
     * @param FilterReady|callable|mixed $filter
     */
    final public static function create($condition, $filter, bool $negation): self
    {
        return $negation
            ? new ConditionalNotFilter($condition, $filter)
            : new ConditionalYesFilter($condition, $filter);
    }
    
    /**
     * @param FilterReady|callable|mixed $condition
     * @param FilterReady|callable|mixed $filter
     */
    final protected function __construct($condition, $filter)
    {
        parent::__construct();

        $this->condition = Filters::getAdapter($condition);
        $this->filter = Filters::getAdapter($filter);
    }

    final public function getMode(): ?int
    {
        $mode = $this->condition->getMode();
        
        return $mode === $this->filter->getMode() ? $mode : null;
    }

    final public function inMode(?int $mode): Filter
    {
        return $this;
    }

    final public function equals(Filter $other): bool
    {
        return $other instanceof $this
            && $other->condition->equals($this->condition)
            && $other->filter->equals($this->filter);
    }
}