<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Mapping\MapWhileUntil;

use FiiSoft\Jackdaw\Condition\Condition;
use FiiSoft\Jackdaw\Condition\ConditionReady;
use FiiSoft\Jackdaw\Condition\Conditions;
use FiiSoft\Jackdaw\Mapper\Mapper;
use FiiSoft\Jackdaw\Mapper\MapperReady;
use FiiSoft\Jackdaw\Mapper\Mappers;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

abstract class MapWhileUntil extends BaseOperation
{
    protected Condition $condition;
    protected Mapper $mapper;
    
    protected bool $isActive = true;
    
    /**
     * @param ConditionReady|callable $condition
     * @param MapperReady|callable|iterable|mixed $mapper
     */
    final public function __construct($condition, $mapper)
    {
        $this->condition = Conditions::getAdapter($condition);
        $this->mapper = Mappers::getAdapter($mapper);
    }
}