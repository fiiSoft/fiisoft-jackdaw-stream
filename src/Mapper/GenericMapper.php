<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Mapper\Exception\MapperExceptionFactory;
use FiiSoft\Jackdaw\Mapper\Generic\OneArg;
use FiiSoft\Jackdaw\Mapper\Generic\TwoArgs;
use FiiSoft\Jackdaw\Mapper\Generic\ZeroArg;
use FiiSoft\Jackdaw\Mapper\Internal\StateMapper;

abstract class GenericMapper extends StateMapper
{
    /** @var callable */
    protected $callable;
    
    final public static function create(callable $mapper): self
    {
        $numOfArgs = Helper::getNumOfArgs($mapper);
        
        switch ($numOfArgs) {
            case 1:
                return new OneArg($mapper);
            case 2:
                return new TwoArgs($mapper);
            case 0:
                return new ZeroArg($mapper);
            default:
                throw MapperExceptionFactory::invalidParamMapper($numOfArgs);
        }
        
    }
    
    final protected function __construct(callable $mapper)
    {
        $this->callable = $mapper;
    }
}