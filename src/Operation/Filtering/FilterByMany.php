<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Filtering;

use FiiSoft\Jackdaw\Filter\Adjuster\UnwrapFilterAdjuster;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Filtering\FilterData\FilterFieldData;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class FilterByMany extends BaseOperation
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
        $allYes = $allNo = true;
        
        foreach ($this->checks as $check) {
            if ($check->negation) {
                if ($allYes) {
                    $allYes = false;
                }
            } elseif ($allNo) {
                $allNo = false;
            }
        }
        
        if ($allYes) {
            return $this->buildFilterStream($stream);
        }
        
        if ($allNo) {
            return $this->buildOmitStream($stream);
        }
        
        return $this->buildMixedStream($stream);
    }
    
    /**
     * @param iterable<mixed, mixed> $stream
     * @return iterable<mixed, mixed>
     */
    private function buildFilterStream(iterable $stream): iterable
    {
        $this->unwrapNestedFilters();
        
        foreach ($stream as $key => $value) {
            foreach ($this->checks as $check) {
                if ($check->filter->isAllowed($value[$check->field], $key)) {
                    continue;
                }
                
                continue 2;
            }
            
            yield $key => $value;
        }
    }
    
    /**
     * @param iterable<mixed, mixed> $stream
     * @return iterable<mixed, mixed>
     */
    private function buildOmitStream(iterable $stream): iterable
    {
        $this->unwrapNestedFilters();
        
        foreach ($stream as $key => $value) {
            foreach ($this->checks as $check) {
                if ($check->filter->isAllowed($value[$check->field], $key)) {
                    continue 2;
                }
            }
            
            yield $key => $value;
        }
    }
    
    private function unwrapNestedFilters(): void
    {
        foreach ($this->checks as $check) {
            $check->filter = UnwrapFilterAdjuster::unwrap($check->filter);
        }
    }
    
    /**
     * @param iterable<mixed, mixed> $stream
     * @return iterable<mixed, mixed>
     */
    private function buildMixedStream(iterable $stream): iterable
    {
        foreach ($this->checks as $check) {
            if ($check->negation) {
                $check->negation = false;
                $check->filter = $check->filter->negate();
            }
        }

        return $this->buildFilterStream($stream);
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
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->checks = [];
            
            parent::destroy();
        }
    }
}