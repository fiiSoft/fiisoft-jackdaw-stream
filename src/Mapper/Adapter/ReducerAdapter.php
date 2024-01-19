<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper\Adapter;

use FiiSoft\Jackdaw\Mapper\Internal\StateMapper;
use FiiSoft\Jackdaw\Reducer\Reducer;

final class ReducerAdapter extends StateMapper
{
    private Reducer $reducer;
    
    public function __construct(Reducer $reducer)
    {
        $this->reducer = $reducer;
    }
    
    /**
     * @inheritDoc
     */
    public function map($value, $key = null)
    {
        $this->reducer->reset();
        
        foreach ($value as $v) {
            $this->reducer->consume($v);
        }
        
        return $this->reducer->hasResult() ? $this->reducer->result() : null;
    }
    
    protected function buildValueMapper(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            $this->reducer->reset();
            
            foreach ($value as $v) {
                $this->reducer->consume($v);
            }
            
            yield $key => $this->reducer->hasResult() ? $this->reducer->result() : null;
        }
    }
}