<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Logic;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\FilterReady;
use FiiSoft\Jackdaw\Filter\Filters;

abstract class TwoArgsLogicFilter extends BaseLogicFilter
{
    protected Filter $first, $second;
    
    /**
     * @param FilterReady|callable|mixed $first
     * @param FilterReady|callable|mixed $second
     */
    abstract protected static function create($first, $second, ?int $mode = null): Filter;
    
    /**
     * @param FilterReady|callable|mixed $first
     * @param FilterReady|callable|mixed $second
     */
    protected function __construct($first, $second, ?int $mode = null)
    {
        $this->first = Filters::getAdapter($first, $mode);
        $this->second = Filters::getAdapter($second, $mode);
    }
    
    final public function inMode(?int $mode): Filter
    {
        return $mode !== null && $mode !== $this->getMode()
            ? static::create($this->first, $this->second, $mode)
            : $this;
    }
    
    final public function equals(Filter $other): bool
    {
        return $other === $this || $other instanceof $this
            && $other->first->equals($this->first)
            && $other->second->equals($this->second);
    }
    
    /**
     * @inheritDoc
     */
    final protected function collectFilters(): array
    {
        return [$this->first, $this->second];
    }
    
    /**
     * @inheritDoc
     */
    final protected function createFilter(array $filters, ?int $mode = null): Filter
    {
        return static::create($filters[0], $filters[1], $mode);
    }
}