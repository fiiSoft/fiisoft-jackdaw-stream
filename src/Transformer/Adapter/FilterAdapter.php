<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Transformer\Adapter;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Transformer\Transformer;

final class FilterAdapter implements Transformer
{
    private Filter $filter;
    
    public function __construct(Filter $filter)
    {
        $this->filter = $filter;
    }
    
    /**
     * @inheritDoc
     */
    public function transform($value, $key): array
    {
        $result = [];
        
        foreach ($value as $k => $v) {
            if ($this->filter->isAllowed($v, $k)) {
                $result[$k] = $v;
            }
        }
        
        return $result;
    }
}