<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Transformer;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Mapper\Mapper;
use FiiSoft\Jackdaw\Reducer\Reducer;
use FiiSoft\Jackdaw\Transformer\Adapter\FilterAdapter;
use FiiSoft\Jackdaw\Transformer\Adapter\MapperAdapter;
use FiiSoft\Jackdaw\Transformer\Adapter\PhpSortingFunctionAdapter;
use FiiSoft\Jackdaw\Transformer\Adapter\ReducerAdapter;

final class Transformers
{
    private const PHP_SORT_FUNC = [
        'sort', '\sort', 'rsort', '\rsort',
        'asort', '\asort', 'arsort', '\arsort',
        'ksort', '\ksort', 'krsort', '\krsort',
    ];
    
    /**
     * @param Transformer|Mapper|Reducer|Filter|callable|null $transformer
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
        
        if ($transformer instanceof Filter) {
            return new FilterAdapter($transformer);
        }
        
        if (\is_callable($transformer)) {
            if (\is_string($transformer) && \in_array($transformer, self::PHP_SORT_FUNC, true)) {
                return new PhpSortingFunctionAdapter($transformer);
            }
            
            return GenericTransformer::create($transformer);
        }
        
        throw InvalidParamException::describe('transformer', $transformer);
    }
}