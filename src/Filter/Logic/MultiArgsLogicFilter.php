<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Logic;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\Filters;

abstract class MultiArgsLogicFilter extends LogicFilter
{
    /** @var Filter[] */
    protected array $filters = [];
    
    protected ?int $mode = null;
    
    /**
     * @param array<Filter|callable|mixed> $filters
     */
    abstract protected static function create(array $filters, ?int $mode = null): self;
    
    /**
     * @param array<Filter|callable|mixed> $filters
     */
    protected function __construct(array $filters, ?int $mode = null)
    {
        parent::__construct();
        
        if (empty($filters)) {
            throw InvalidParamException::byName('filters');
        }
        
        foreach ($filters as $filter) {
            $this->filters[] = Filters::getAdapter($filter, $mode);
        }
        
        $this->mode = $mode;
    }
    
    final public function getMode(): ?int
    {
        if ($this->mode !== null) {
            return $this->mode;
        }
        
        foreach ($this->filters as $filter) {
            if ($this->mode === null) {
                $this->mode = $filter->getMode();
            } elseif ($this->mode !== $filter->getMode()) {
                $this->mode = null;
                break;
            }
        }
        
        return $this->mode;
    }
    
    /**
     * @return Filter[]
     */
    final protected function negatedFilters(): array
    {
        return \array_map(static fn(Filter $filter): Filter => $filter->negate(), $this->filters);
    }
    
    final public function inMode(?int $mode): Filter
    {
        return $mode !== null && $mode !== $this->getMode()
            ? static::create($this->filters, $mode)
            : $this;
    }
}