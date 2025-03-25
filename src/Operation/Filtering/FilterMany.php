<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Filtering;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\Logic\ConditionalFilter;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Filtering\FilterData\FilterConditionalData;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Operation\Internal\Operations;
use FiiSoft\Jackdaw\Operation\Internal\SingularOperation;
use FiiSoft\Jackdaw\Operation\Operation;

final class FilterMany extends BaseOperation implements SingularOperation
{
    /** @var FilterConditionalData[] */
    private array $checks = [];
    
    /** @var Filter[] */
    private array $filters = [];
    
    public function __construct(StackableFilter $first, ?StackableFilter $second = null)
    {
        $this->add($first);
        
        if ($second !== null) {
            $this->add($second);
        }
    }
    
    public function prepare(): void
    {
        parent::prepare();
        
        $this->prepareFilters();
    }
    
    public function handle(Signal $signal): void
    {
        foreach ($this->filters as $filter) {
            if ($filter->isAllowed($signal->item->value, $signal->item->key)) {
                continue;
            }
            
            return;
        }
        
        $this->next->handle($signal);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($this->filters as $filter) {
            $stream = $filter->buildStream($stream);
        }
        
        return $stream;
    }
    
    public function add(StackableFilter $filter): void
    {
        $newOne = $filter->filterData();
        
        foreach ($this->checks as $check) {
            if ($check->mergeWith($newOne)) {
                return;
            }
        }
        
        $this->checks[] = $newOne;
    }
    
    /**
     * @return FilterConditionalData[]
     */
    public function getChecks(): array
    {
        return $this->checks;
    }
    
    public function isSingular(): bool
    {
        return \count($this->checks) === 1;
    }
    
    public function getSingular(): Operation
    {
        if (empty($this->filters)) {
            $this->prepareFilters();
        }
        
        return Operations::filter($this->filters[0]);
    }
    
    private function prepareFilters(): void
    {
        foreach ($this->checks as $check) {
            if ($check->condition === null) {
                $this->filters[] = $check->negation ? $check->filter->negate() : $check->filter;
            } else {
                $this->filters[] = ConditionalFilter::create($check->condition, $check->filter, $check->negation);
            }
        }
        
        $this->checks = [];
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->checks = [];
            $this->filters = [];
            
            parent::destroy();
        }
    }
}