<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Logic\OpOR;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\FilterReady;
use FiiSoft\Jackdaw\Filter\Logic\MultiArgsLogicFilter;
use FiiSoft\Jackdaw\Filter\Logic\OpAND\BaseAND;
use FiiSoft\Jackdaw\Internal\Check;

abstract class BaseOR extends MultiArgsLogicFilter
{
    /**
     * @param array<FilterReady|callable|mixed> $filters
     */
    final public static function create(array $filters, ?int $mode = null): BaseOR
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
        
        return $mode === Check::BOTH ? new FilterORBoth($collection) : new FilterOR($collection, $mode);
    }
    
    final public function negate(): Filter
    {
        return BaseAND::create($this->negatedFilters(), $this->negatedMode());
    }
}