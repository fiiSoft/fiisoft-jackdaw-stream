<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Transformer\Adapter;

use FiiSoft\Jackdaw\Reducer\Reducer;
use FiiSoft\Jackdaw\Transformer\Transformer;

final class ReducerAdapter implements Transformer
{
    private Reducer $reducer;
    
    public function __construct(Reducer $reducer)
    {
        $this->reducer = $reducer;
    }
    
    /**
     * @inheritDoc
     */
    public function transform($value, $key)
    {
        $this->reducer->reset();
        
        foreach ($value as $v) {
            $this->reducer->consume($v);
        }
        
        return $this->reducer->hasResult() ? $this->reducer->result() : null;
    }
}