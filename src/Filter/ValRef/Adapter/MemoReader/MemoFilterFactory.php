<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\ValRef\Adapter\MemoReader;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\String\StringFilter;
use FiiSoft\Jackdaw\Filter\ValRef\FilterAdapterFactory;
use FiiSoft\Jackdaw\Filter\ValRef\FilterPicker;
use FiiSoft\Jackdaw\Memo\MemoReader;

final class MemoFilterFactory extends FilterAdapterFactory
{
    private MemoReader $reader;
    
    public function __construct(MemoReader $reader, FilterPicker $filterPicker)
    {
        parent::__construct($filterPicker);
        
        $this->reader = $reader;
    }
    
    public function createFilter(Filter $filter): Filter
    {
        return new MemoFilter($this->reader, $filter);
    }
    
    public function createStringFilter(StringFilter $filter): StringFilter
    {
        return new MemoStringFilter($this->reader, $filter);
    }
}