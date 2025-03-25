<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Filtering;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Filtering\FilterData\FilterFieldData;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Operation\Internal\Operations;
use FiiSoft\Jackdaw\Operation\Internal\SingularOperation;
use FiiSoft\Jackdaw\Operation\Operation;

final class FilterByMany extends BaseOperation implements SingularOperation
{
    /** @var FilterFieldData[] */
    private array $checks = [];
    
    public function __construct(StackableFilterBy $first, ?StackableFilterBy $second = null)
    {
        $this->add($first);
        
        if ($second !== null) {
            $this->add($second);
        }
    }
    
    public function handle(Signal $signal): void
    {
        foreach ($this->checks as $check) {
            if ($check->negation === $check->filter->isAllowed(
                $signal->item->value[$check->field],
                $signal->item->key
            )) {
                return;
            }
        }
        
        $this->next->handle($signal);
    }
    
    /**
     * @inheritDoc
     */
    public function buildStream(iterable $stream): iterable
    {
        foreach ($this->checks as $check) {
            $stream = $check->buildStream($stream);
        }
        
        return $stream;
    }
    
    public function add(StackableFilterBy $filter): void
    {
        $newOne = $filter->filterByData();
        
        foreach ($this->checks as $check) {
            if ($check->mergeWith($newOne)) {
                return;
            }
        }
        
        $this->checks[] = $newOne;
    }
    
    /**
     * @return FilterFieldData[]
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
        $check = $this->checks[0];
        $filter = $check->negation ? $check->filter->negate() : $check->filter;
        
        return Operations::filterBy($check->field, $filter);
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->checks = [];
            
            parent::destroy();
        }
    }
}