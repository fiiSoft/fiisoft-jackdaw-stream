<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Logic\OpXOR;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\FilterReady;
use FiiSoft\Jackdaw\Filter\Logic\OpXNOR\BaseXNOR;
use FiiSoft\Jackdaw\Filter\Logic\TwoArgsLogicFilter;
use FiiSoft\Jackdaw\Internal\Check;

abstract class BaseXOR extends TwoArgsLogicFilter
{
    /**
     * @param FilterReady|callable|mixed $first
     * @param FilterReady|callable|mixed $second
     */
    final public static function create($first, $second, ?int $mode = null): Filter
    {
        if ($mode === Check::BOTH) {
            return new FilterXORBoth($first, $second);
        }
        
        if ($mode === Check::ANY) {
            return new FilterXORAny($first, $second);
        }
        
        return new FilterXOR($first, $second, $mode);
    }
    
    final public function negate(): Filter
    {
        return BaseXNOR::create($this->first, $this->second, $this->negatedMode());
    }
}