<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Adjuster;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\FilterAdjuster;
use FiiSoft\Jackdaw\Filter\FilterWrapper;

final class UnwrapFilterAdjuster implements FilterAdjuster
{
    private static ?self $instance = null;
    
    public static function unwrap(Filter $filter): Filter
    {
        if (self::$instance === null) {
            //@codeCoverageIgnoreStart
            self::$instance = new self();
            //@codeCoverageIgnoreEnd
        }
        
        return $filter->adjust(self::$instance);
    }
    
    public function adjust(Filter $filter): Filter
    {
        if ($filter instanceof FilterWrapper) {
            $wrapped = $filter->wrappedFilter();
            $adjusted = $wrapped->adjust($this);
            
            if ($adjusted->equals($wrapped)) {
                return $wrapped;
            }
            
            return $adjusted;
        }
        
        return $filter;
    }
}