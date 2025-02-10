<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Logic\OpAND;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\FilterReady;
use FiiSoft\Jackdaw\Filter\Logic\MultiArgsLogicFilter;
use FiiSoft\Jackdaw\Filter\Logic\OpOR\BaseOR;
use FiiSoft\Jackdaw\Internal\Check;

abstract class BaseAND extends MultiArgsLogicFilter
{
    /**
     * @param array<FilterReady|callable|mixed> $filters
     */
    final public static function create(array $filters, ?int $mode = null): BaseAND
    {
        $collection = [];
        
        foreach ($filters as $filter) {
            if ($filter instanceof self) {
                foreach ($filter->filters as $f) {
                    $collection[] = $f;
                }
            } else {
                $collection[] = $filter;
            }
        }
        
        return $mode === Check::ANY ? new FilterANDAny($collection) : new FilterAND($collection, $mode);
    }
    
    final public function negate(): Filter
    {
        return BaseOR::create($this->negatedFilters(), $this->negatedMode());
    }
}