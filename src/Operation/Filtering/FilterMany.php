<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Filtering;

use FiiSoft\Jackdaw\Filter\Internal\FilterData;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class FilterMany extends BaseOperation
{
    /** @var FilterData[] */
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
            if ($check->condition === null
                || $check->condition->isTrueFor($signal->item->value, $signal->item->key)
            ) {
                if ($check->negation === $check->filter->isAllowed(
                        $signal->item->value,
                        $signal->item->key,
                    )) {
                    return;
                }
            }
        }
        
        $this->next->handle($signal);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        return $this->areAllFiltersUnconditional()
            ? $this->buildOptimisedStream($stream)
            : $this->buildStandardStream($stream);
    }
    
    /**
     * @param iterable<mixed, mixed> $stream
     * @return iterable<mixed, mixed>
     */
    private function buildOptimisedStream(iterable $stream): iterable
    {
        foreach ($this->checks as $check) {
            $stream = $check->negation
                ? $check->filter->negate()->buildStream($stream)
                : $check->filter->buildStream($stream);
        }

        return $stream;
    }
    
    /**
     * @param iterable<mixed, mixed> $stream
     * @return iterable<mixed, mixed>
     */
    private function buildStandardStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            foreach ($this->checks as $check) {
                if (($check->condition === null || $check->condition->isTrueFor($value, $key))
                    && $check->negation === $check->filter->isAllowed($value, $key)
                ) {
                    continue 2;
                }
            }
            
            yield $key => $value;
        }
    }
    
    private function areAllFiltersUnconditional(): bool
    {
        foreach ($this->checks as $check) {
            if ($check->condition !== null) {
                return false;
            }
        }
        
        return true;
    }
    
    public function add(StackableFilter $filter): void
    {
        $this->checks[] = $filter->filterData();
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->checks = [];
            
            parent::destroy();
        }
    }
}