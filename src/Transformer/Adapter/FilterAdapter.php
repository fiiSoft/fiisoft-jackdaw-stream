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
    public function transform($value, $key)
    {
        if (\is_iterable($value)) {
            $result = [];
            
            foreach ($value as $index => $item) {
                if ($this->filter->isAllowed($item, $index)) {
                    $result[$index] = $item;
                }
            }
            
            return $result;
        }
        
        throw new \LogicException('Param value must be iterable');
    }
}