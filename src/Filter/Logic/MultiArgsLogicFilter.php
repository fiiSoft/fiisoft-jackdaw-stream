<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Logic;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\FilterReady;
use FiiSoft\Jackdaw\Filter\Filters;

abstract class MultiArgsLogicFilter extends BaseMultiLogicFilter
{
    /** @var Filter[] */
    protected array $filters = [];
    
    /**
     * @param array<FilterReady|callable|mixed> $filters
     */
    abstract protected static function create(array $filters, ?int $mode = null): Filter;
    
    /**
     * @param array<FilterReady|callable|mixed> $filters
     */
    protected function __construct(array $filters, ?int $mode = null)
    {
        if (empty($filters)) {
            throw InvalidParamException::byName('filters');
        }
        
        foreach ($filters as $filter) {
            $this->filters[] = Filters::getAdapter($filter, $mode);
        }
        
        $this->mode = $mode;
    }
    
    final public function inMode(?int $mode): Filter
    {
        return $mode !== null && $mode !== $this->getMode()
            ? $this->createFilter($this->filters, $mode)
            : $this;
    }
    
    /**
     * @inheritDoc
     */
    final protected function createFilter(array $filters, ?int $mode = null): Filter
    {
        return static::create($filters, $mode);
    }
    
    /**
     * @inheritDoc
     */
    final protected function collectFilters(): array
    {
        return $this->filters;
    }
}