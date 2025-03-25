<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Logic;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\FilterReady;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Memo\SequencePredicate;

abstract class MultiArgsLogicFilter extends BaseMultiLogicFilter
{
    /** @var Filter[] */
    protected array $filters = [];
    
    /**
     * @param array<FilterReady|callable|array<string|int, mixed>|scalar> $filters
     */
    abstract protected static function create(array $filters, ?int $mode = null): Filter;
    
    /**
     * Helper method.
     *
     * @param array<FilterReady|callable|array<string|int, mixed>|scalar> $filters
     * @return array<FilterReady|callable|array<string|int, mixed>|scalar>
     */
    final protected static function removeDuplicates(array $filters): array
    {
        $max = \count($filters) - 1;
        
        for ($i = 0; $i < $max; ++$i) {
            $filter = $filters[$i];
            
            for ($j = $i + 1; $j <= $max; ++$j) {
                if (self::areFiltersTheSame($filter, $filters[$j])) {
                    unset($filters[$j]);
                    $filters = \array_values($filters);
                    --$max;
                }
            }
        }
        
        return $filters;
    }
    
    /**
     * @param FilterReady|callable|array<string|int, mixed>|scalar $filter
     * @param FilterReady|callable|array<string|int, mixed>|scalar $other
     */
    private static function areFiltersTheSame($filter, $other): bool
    {
        return $filter instanceof Filter && $other instanceof Filter && $other->equals($filter)
            || $filter instanceof SequencePredicate && $other instanceof SequencePredicate && $other->equals($filter)
            || $other === $filter;
    }
    
    /**
     * @param array<FilterReady|callable|array<string|int, mixed>|scalar> $filters
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