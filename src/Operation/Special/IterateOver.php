<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Special;

use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Operation\Exception\OperationExceptionFactory;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Operation\Special\IterateOver\OneArgIterateOver;
use FiiSoft\Jackdaw\Operation\Special\IterateOver\TwoArgsIterateOver;
use FiiSoft\Jackdaw\Operation\Special\IterateOver\ZeroArgIterateOver;

abstract class IterateOver extends BaseOperation
{
    /** @var callable */
    protected $callable;
    
    final public static function create(callable $producer): self
    {
        $numOfArgs = Helper::getNumOfArgs($producer);
        
        switch ($numOfArgs) {
            case 1:
                return new OneArgIterateOver($producer);
            case 2:
                return new TwoArgsIterateOver($producer);
            case 0:
                return new ZeroArgIterateOver($producer);
            default:
                throw OperationExceptionFactory::invalidIterateOverCallback($numOfArgs);
        }
    }
    
    final protected function __construct(callable $producer)
    {
        if (Helper::isDeclaredReturnTypeIterable($producer)) {
            $this->callable = $producer;
        } else {
            throw OperationExceptionFactory::wrongTypeOfIterateOverCallback();
        }
    }
}