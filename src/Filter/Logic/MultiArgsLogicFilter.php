<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Logic;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\FilterReady;
use FiiSoft\Jackdaw\Filter\Filters;

abstract class MultiArgsLogicFilter extends BaseCompoundFilter
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
        parent::__construct();
        
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
            ? static::create($this->filters, $mode)
            : $this;
    }
    
    final public function getFilters(): array
    {
        return $this->filters;
    }
}