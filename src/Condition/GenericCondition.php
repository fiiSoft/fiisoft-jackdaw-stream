<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Condition;

use FiiSoft\Jackdaw\Condition\Exception\ConditionExceptionFactory;
use FiiSoft\Jackdaw\Condition\Generic\OneArg;
use FiiSoft\Jackdaw\Condition\Generic\TwoArgs;
use FiiSoft\Jackdaw\Condition\Generic\ZeroArg;
use FiiSoft\Jackdaw\Internal\Helper;

abstract class GenericCondition implements Condition
{
    /** @var callable */
    protected $callable;
    
    final public static function create(callable $condition): self
    {
        $numOfArgs = Helper::getNumOfArgs($condition);
        
        switch ($numOfArgs) {
            case 1:
                return new OneArg($condition);
            case 2:
                return new TwoArgs($condition);
            case 0:
                return new ZeroArg($condition);
            default:
                throw ConditionExceptionFactory::invalidParamCondition($numOfArgs);
        }
    }
    
    final protected function __construct(callable $condition)
    {
        $this->callable = $condition;
    }
}