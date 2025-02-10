<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\ValRef\Adapter\MemoReader;

use FiiSoft\Jackdaw\Filter\ValRef\FilterAdapterFactory;
use FiiSoft\Jackdaw\Filter\ValRef\FilterPicker;
use FiiSoft\Jackdaw\Memo\MemoReader;

final class MemoFilterPicker extends FilterPicker
{
    private MemoReader $reader;
    
    public function __construct(MemoReader $reader, bool $isNot = false)
    {
        parent::__construct($isNot);
        
        $this->reader = $reader;
    }
    
    protected function createFactory(): FilterAdapterFactory
    {
        return new MemoFilterFactory($this->reader, $this);
    }
}