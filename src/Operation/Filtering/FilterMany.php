<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Filtering;

use FiiSoft\Jackdaw\Filter\Logic\ConditionalFilter;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Filtering\FilterData\FilterConditionalData;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class FilterMany extends BaseOperation
{
    /** @var FilterConditionalData[] */
    private array $checks = [];
    
    public function __construct(StackableFilter $first, ?StackableFilter $second = null)
    {
        $this->add($first);
        
        if ($second !== null) {
            $this->add($second);
        }
    }
    
    public function handle(Signal $signal): void
    {
        foreach ($this->checks as $check) {
            if ($check->condition === null || $check->condition->isAllowed($signal->item->value, $signal->item->key)) {
                if ($check->negation === $check->filter->isAllowed($signal->item->value, $signal->item->key)) {
                    return;
                }
            }
        }
        
        $this->next->handle($signal);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($this->checks as $check) {
            if ($check->condition === null) {
                $filter = $check->negation ? $check->filter->negate() : $check->filter;
            } else {
                $filter = ConditionalFilter::create($check->condition, $check->filter, $check->negation);
            }
            
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
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->checks = [];
            
            parent::destroy();
        }
    }
    
    /**
     * @return FilterConditionalData[]
     */
    public function getChecks(): array
    {
        return $this->checks;
    }
}