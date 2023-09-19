<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Logic;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\Filters;

abstract class LogicFilter implements Filter
{
    /** @var Filter[] */
    protected array $filters = [];
    
    /**
     * @param array<Filter|callable|mixed> $filters
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