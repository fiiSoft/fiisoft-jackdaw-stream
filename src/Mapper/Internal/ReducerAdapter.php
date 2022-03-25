<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper\Internal;

use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Reducer\Reducer;

final class ReducerAdapter extends BaseMapper
{
    private Reducer $reducer;
    
    public function __construct(Reducer $reducer)
    {
        $this->reducer = $reducer;
    }
    
    /**
     * @inheritDoc
     */
    public function map($value, $key)
    {
        if (\is_iterable($value)) {
            $this->reducer->reset();
            
            foreach ($value as $item) {
                $this->reducer->consume($item);
            }
    
            return $this->reducer->hasResult() ? $this->reducer->result() : null;
        }
        
        throw new \LogicException('Unable to reduce '.Helper::typeOfParam($value).' because it is not iterable');
    }
}