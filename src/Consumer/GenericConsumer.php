<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Consumer;

use FiiSoft\Jackdaw\Consumer\Exception\ConsumerExceptionFactory;
use FiiSoft\Jackdaw\Consumer\Generic\OneArg;
use FiiSoft\Jackdaw\Consumer\Generic\TwoArgs;
use FiiSoft\Jackdaw\Consumer\Generic\ZeroArg;
use FiiSoft\Jackdaw\Internal\Helper;

abstract class GenericConsumer implements Consumer
{
    /** @var callable */
    protected $callable;
    
    final public static function create(callable $consumer): self
    {
        $numOfArgs = Helper::getNumOfArgs($consumer);
        
        switch ($numOfArgs) {
            case 0:
                return new ZeroArg($consumer);
            case 1:
                return new OneArg($consumer);
            case 2:
                return new TwoArgs($consumer);
            default:
                throw ConsumerExceptionFactory::invalidParamConsumer($numOfArgs);
        }
    }
    
    final protected function __construct(callable $consumer)
    {
        $this->callable = $consumer;
    }
}