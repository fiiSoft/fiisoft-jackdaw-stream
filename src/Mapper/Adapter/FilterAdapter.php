<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper\Adapter;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Mapper\Internal\StateMapper;

final class FilterAdapter extends StateMapper
{
    private Filter $filter;
    
    public function __construct(Filter $filter)
    {
        $this->filter = $filter;
    }
    
    /**
     * @param mixed $value
     * @param mixed $key
     * @return array<string|int, mixed>
     */
    public function map($value, $key = null): array
    {
        $result = [];
        
        foreach ($value as $k => $v) {
            if ($this->filter->isAllowed($v, $k)) {
                $result[$k] = $v;
            }
        }
        
        return $result;
    }
    
    protected function buildValueMapper(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            $result = [];
            
            foreach ($value as $k => $v) {
                if ($this->filter->isAllowed($v, $k)) {
                    $result[$k] = $v;
                }
            }
            
            yield $key => $result;
        }
    }
}