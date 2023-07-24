<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Transformer;

use FiiSoft\Jackdaw\Mapper\Mapper;
use FiiSoft\Jackdaw\Reducer\Reducer;
use FiiSoft\Jackdaw\Transformer\Adapter\MapperAdapter;
use FiiSoft\Jackdaw\Transformer\Adapter\ReducerAdapter;

final class Transformers
{
    /**
     * @param Transformer|Mapper|Reducer|callable|null $transformer
     */
    public static function getAdapter($transformer): ?Transformer
    {
        if ($transformer === null) {
            return null;
        }
        
        if ($transformer instanceof Transformer) {
            return $transformer;
        }
    
        if ($transformer instanceof Reducer) {
            return new ReducerAdapter($transformer);
        }
    
        if ($transformer instanceof Mapper) {
            return new MapperAdapter($transformer);
        }
    
        if (\is_callable($transformer)) {
            return new GenericTransformer($transformer);
        }
        
        throw new \InvalidArgumentException('Invalid param transformer');
    }
}