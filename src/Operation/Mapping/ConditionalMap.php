<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Mapping;

use FiiSoft\Jackdaw\Condition\Condition;
use FiiSoft\Jackdaw\Condition\ConditionReady;
use FiiSoft\Jackdaw\Condition\Conditions;
use FiiSoft\Jackdaw\Mapper\Mapper;
use FiiSoft\Jackdaw\Mapper\MapperReady;
use FiiSoft\Jackdaw\Mapper\Mappers;
use FiiSoft\Jackdaw\Mapper\Value;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

abstract class ConditionalMap extends BaseOperation
{
    protected Condition $condition;
    
    protected Mapper $mapper;
    protected ?Mapper $elseMapper = null;
    
    /**
     * @param ConditionReady|callable $condition
     * @param MapperReady|callable|iterable|mixed $mapper
     * @param MapperReady|callable|iterable|mixed|null $elseMapper
     */
    public function __construct($condition, $mapper, $elseMapper = null)
    {
        $this->condition = Conditions::getAdapter($condition);
        $this->mapper = Mappers::getAdapter($mapper);
        
        if ($elseMapper !== null) {
            $this->elseMapper = Mappers::getAdapter($elseMapper);
            if ($this->elseMapper instanceof Value) {
                $this->elseMapper = null;
            }
        }
    }
    
    final public function isBarren(): bool
    {
        return $this->elseMapper === null && $this->mapper instanceof Value;
    }
    
    final public function shouldBeNonConditional(): bool
    {
        return $this->elseMapper !== null && $this->mapper->equals($this->elseMapper);
    }
    
    abstract public function getMaper(): Mapper;
}