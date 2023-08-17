<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Internal;

use FiiSoft\Jackdaw\Condition\Condition;
use FiiSoft\Jackdaw\Condition\Conditions;
use FiiSoft\Jackdaw\Discriminator\Discriminator;
use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Mapper\Mapper;
use FiiSoft\Jackdaw\Mapper\Mappers;
use FiiSoft\Jackdaw\Mapper\Value;
use FiiSoft\Jackdaw\Predicate\Predicate;
use FiiSoft\Jackdaw\Reducer\Reducer;

abstract class ConditionalMapOperation extends BaseOperation
{
    protected Condition $condition;
    
    protected Mapper $mapper;
    protected ?Mapper $elseMapper = null;
    
    /**
     * @param Condition|Predicate|Filter|callable $condition
     * @param Mapper|Reducer|Predicate|Filter|Discriminator|callable|array|mixed $mapper
     * @param Mapper|Reducer|Predicate|Filter|Discriminator|callable|array|mixed $elseMapper
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
        if ($this->elseMapper !== null) {
            return $this->mapper === $this->elseMapper
                || $this->mapper instanceof $this->elseMapper && $this->mapper->isStateless();
        }
        
        return false;
    }
    
    abstract public function getMaper(): Mapper;
}