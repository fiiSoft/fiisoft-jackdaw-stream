<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Logic\OpOR;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\FilterReady;
use FiiSoft\Jackdaw\Filter\Logic\MultiArgsLogicFilter;
use FiiSoft\Jackdaw\Filter\Logic\OpAND\BaseAND;
use FiiSoft\Jackdaw\Filter\Logic\OpOR\Optim\FiveArgsOR;
use FiiSoft\Jackdaw\Filter\Logic\OpOR\Optim\FourArgsOR;
use FiiSoft\Jackdaw\Filter\Logic\OpOR\Optim\SevenArgsOR;
use FiiSoft\Jackdaw\Filter\Logic\OpOR\Optim\SixArgsOR;
use FiiSoft\Jackdaw\Filter\Logic\OpOR\Optim\ThreeArgsOR;
use FiiSoft\Jackdaw\Filter\Logic\OpOR\Optim\TwoArgsOR;
use FiiSoft\Jackdaw\Internal\Check;

abstract class BaseOR extends MultiArgsLogicFilter
{
    /**
     * @param array<FilterReady|callable|mixed> $filters
     */
    final public static function create(array $filters, ?int $mode = null): Filter
    {
        $collection = self::flatFilters($filters);
        
        if ($mode === Check::BOTH) {
            return new FilterORBoth($collection);
        }
        
        $args = $collection;
        $args[] = $mode;
        
        switch (\count($collection)) {
            case 2: return new TwoArgsOR(...$args);
            case 3: return new ThreeArgsOR(...$args);
            case 4: return new FourArgsOR(...$args);
            case 5: return new FiveArgsOR(...$args);
            case 6: return new SixArgsOR(...$args);
            case 7: return new SevenArgsOR(...$args);
            default: return new FilterOR($collection, $mode);
        }
    }
    
    /**
     * Helper method.
     *
     * @param array<FilterReady|callable|mixed> $filters
     * @return array<FilterReady|callable|mixed>
     */
    private static function flatFilters(array $filters): array
    {
        $collection = [];
        
        foreach ($filters as $filter) {
            if ($filter instanceof LogicOR) {
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
        return BaseAND::create($this->negatedFilters(), $this->negatedMode());
    }
}