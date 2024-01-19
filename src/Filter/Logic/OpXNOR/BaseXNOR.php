<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Logic\OpXNOR;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\Logic\OpXOR\BaseXOR;
use FiiSoft\Jackdaw\Filter\Logic\TwoArgsLogicFilter;
use FiiSoft\Jackdaw\Internal\Check;

abstract class BaseXNOR extends TwoArgsLogicFilter
{
    /**
     * @param Filter|callable|mixed $first
     * @param Filter|callable|mixed $second
     */
    final public static function create($first, $second, ?int $mode = null): Filter
    {
        if ($mode === Check::BOTH) {
            return new FilterXNORBoth($first, $second);
        }
        
        if ($mode === Check::ANY) {
            return new FilterXNORAny($first, $second);
        }
        
        return new FilterXNOR($first, $second, $mode);
    }
    
    final public function negate(): Filter
    {
        return BaseXOR::create($this->first, $this->second, $this->negatedMode());
    }
}