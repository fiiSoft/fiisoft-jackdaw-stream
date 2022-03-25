<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Logic;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Predicate\Predicate;

abstract class LogicFilter implements Filter
{
    /** @var Filter[] */
    protected array $filters = [];
    
    /**
     * @param Filter[]|Predicate[]||callable[]|array $filters
     */
    public function __construct(array $filters)
    {
        if (empty($filters)) {
            throw new \InvalidArgumentException('Param filters cannot be empty');
        }
        
        foreach ($filters as $filter) {
            $this->filters[] = Filters::getAdapter($filter);
        }
    }
}