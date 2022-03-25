<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Logic;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Predicate\Predicate;

final class FilterXOR extends LogicFilter
{
    /**
     * @param Filter|Predicate|callable|mixed $first
     * @param Filter|Predicate|callable|mixed $second
     */
    public function __construct($first, $second)
    {
        parent::__construct([$first, $second]);
    }
    
    public function isAllowed($value, $key, int $mode = Check::VALUE): bool
    {
        [$first, $second] = $this->filters;
    
        if ($mode === Check::BOTH) {
            foreach ([Check::VALUE, Check::KEY] as $check) {
                if ($first->isAllowed($value, $key, $check) XOR $second->isAllowed($value, $key, $check)) {
                    continue;
                }
                
                return false;
            }
            
            return true;
        }
        
        return $first->isAllowed($value, $key, $mode) XOR $second->isAllowed($value, $key, $mode);
    }
}