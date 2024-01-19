<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Transformer;

use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Transformer\Exception\TransformerExceptionFactory;
use FiiSoft\Jackdaw\Transformer\Generic\OneArg;
use FiiSoft\Jackdaw\Transformer\Generic\TwoArgs;

abstract class GenericTransformer implements Transformer
{
    /** @var callable */
    protected $callable;
    
    final public static function create(callable $transformer): self
    {
        $numOfArgs = Helper::getNumOfArgs($transformer);
        
        switch ($numOfArgs) {
            case 1:
                return new OneArg($transformer);
            case 2:
                return new TwoArgs($transformer);
            default:
                throw TransformerExceptionFactory::invalidParamTransformer($numOfArgs);
        }
    }
    
    final protected function __construct(callable $transformer)
    {
        $this->callable = $transformer;
    }
}