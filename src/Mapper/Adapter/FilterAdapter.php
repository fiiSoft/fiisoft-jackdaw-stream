<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper\Adapter;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Mapper\Internal\BaseMapper;

final class FilterAdapter extends BaseMapper
{
    private Filter $filter;
    
    public function __construct(Filter $filter)
    {
        $this->filter = $filter;
    }
    
    /**
     * @inheritDoc
     */
    public function map($value, $key)
    {
        if (\is_iterable($value)) {
            $result = [];
            
            foreach ($value as $k => $v) {
                if ($this->filter->isAllowed($v, $k)) {
                    $result[$k] = $v;
                }
            }
            
            return $result;
        }
    
        throw new \LogicException(
            'Unable to map '.Helper::typeOfParam($value).' using Filter because it is not iterable'
        );
    }
}