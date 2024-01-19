<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Mapping;

use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Operation\Exception\OperationExceptionFactory;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Operation\Mapping\MapKeyValue\OneArgKVMap;
use FiiSoft\Jackdaw\Operation\Mapping\MapKeyValue\TwoArgsKVMap;
use FiiSoft\Jackdaw\Operation\Mapping\MapKeyValue\ZeroArgKVMap;

abstract class MapKeyValue extends BaseOperation
{
    /** @var callable */
    protected $callable;
    
    final public static function create(callable $keyValueMapper): self
    {
        $numOfArgs = Helper::getNumOfArgs($keyValueMapper);
    
        switch ($numOfArgs) {
            case 1:
                return new OneArgKVMap($keyValueMapper);
            case 2:
                return new TwoArgsKVMap($keyValueMapper);
            case 0:
                return new ZeroArgKVMap($keyValueMapper);
            default:
                throw OperationExceptionFactory::invalidKeyValueMapper($numOfArgs);
        }
    }
    
    final protected function __construct(callable $keyValueMapper)
    {
        if (Helper::isDeclaredReturnTypeArray($keyValueMapper)) {
            $this->callable = $keyValueMapper;
        } else {
            throw OperationExceptionFactory::wrongTypeOfKeyValueMapper();
        }
    }
}