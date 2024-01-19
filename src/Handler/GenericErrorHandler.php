<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Handler;

use FiiSoft\Jackdaw\Handler\Exception\HandlerExceptionFactory;
use FiiSoft\Jackdaw\Handler\Generic\OneArg;
use FiiSoft\Jackdaw\Handler\Generic\ThreeArgs;
use FiiSoft\Jackdaw\Handler\Generic\TwoArgs;
use FiiSoft\Jackdaw\Handler\Generic\ZeroArg;
use FiiSoft\Jackdaw\Internal\Helper;

abstract class GenericErrorHandler implements ErrorHandler
{
    /** @var callable */
    protected $callable;
    
    final public static function create(callable $handler): self
    {
        $numOfArgs = Helper::getNumOfArgs($handler);
        
        switch ($numOfArgs) {
            case 0:
                return new ZeroArg($handler);
            case 1:
                return new OneArg($handler);
            case 2:
                return new TwoArgs($handler);
            case 3:
                return new ThreeArgs($handler);
            default:
                throw HandlerExceptionFactory::invalidParamErrorHandler($numOfArgs);
        }
    }
    
    /**
     * @param callable $handler this callable MUST return true, false or null, see explanation in ErrorHandler
     */
    final protected function __construct(callable $handler)
    {
        $this->callable = $handler;
    }
}