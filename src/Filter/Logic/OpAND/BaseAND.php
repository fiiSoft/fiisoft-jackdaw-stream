<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Logic\OpAND;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\FilterReady;
use FiiSoft\Jackdaw\Filter\Logic\MultiArgsLogicFilter;
use FiiSoft\Jackdaw\Filter\Logic\OpAND\Optim\FiveArgsAND;
use FiiSoft\Jackdaw\Filter\Logic\OpAND\Optim\FourArgsAND;
use FiiSoft\Jackdaw\Filter\Logic\OpAND\Optim\SevenArgsAND;
use FiiSoft\Jackdaw\Filter\Logic\OpAND\Optim\SixArgsAND;
use FiiSoft\Jackdaw\Filter\Logic\OpAND\Optim\ThreeArgsAND;
use FiiSoft\Jackdaw\Filter\Logic\OpAND\Optim\TwoArgsAND;
use FiiSoft\Jackdaw\Filter\Logic\OpOR\BaseOR;
use FiiSoft\Jackdaw\Internal\Check;

abstract class BaseAND extends MultiArgsLogicFilter
{
    /**
     * @param array<FilterReady|callable|array<string|int, mixed>|scalar> $filters
     */
    final public static function create(array $filters, ?int $mode = null): Filter
    {
        $collection = self::removeDuplicates(self::flatFilters($filters));
        
        if ($mode === Check::ANY) {
            return new FilterANDAny($collection);
        }
        
        $args = $collection;
        $args[] = $mode;
        
        switch (\count($collection)) {
            case 2: return new TwoArgsAND(...$args);
            case 3: return new ThreeArgsAND(...$args);
            case 4: return new FourArgsAND(...$args);
            case 5: return new FiveArgsAND(...$args);
            case 6: return new SixArgsAND(...$args);
            case 7: return new SevenArgsAND(...$args);
            default: return new FilterAND($collection, $mode);
        }
    }
    
    /**
     * @param array<FilterReady|callable|array<string|int, mixed>|scalar> $filters
     * @return array<FilterReady|callable|array<string|int, mixed>|scalar>
     */
    private static function flatFilters(array $filters): array
    {
        $collection = [];
        
        foreach ($filters as $filter) {
            if ($filter instanceof LogicAND) {
                foreach ($filter->getFilters() as $f) {
                    $collection[] = $f;
                }
            } else {
                $collection[] = $filter;
            }
        }
        
        return $collection;
    }
    
    final public function negate(): Filter
    {
        return BaseOR::create($this->negatedFilters(), $this->negatedMode());
    }
}