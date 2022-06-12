<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Condition\Condition;
use FiiSoft\Jackdaw\Condition\Conditions;
use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Mapper\Mapper;
use FiiSoft\Jackdaw\Mapper\Mappers;
use FiiSoft\Jackdaw\Mapper\Value;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Predicate\Predicate;
use FiiSoft\Jackdaw\Reducer\Reducer;

final class MapWhen extends BaseOperation
{
    private Condition $condition;
    
    private Mapper $mapper;
    private ?Mapper $elseMapper = null;
    
    /**
     * @param Condition|Predicate|Filter|callable $condition
     * @param Mapper|Reducer|callable|mixed $mapper
     * @param Mapper|Reducer|callable|mixed|null $elseMapper
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
    
    public function handle(Signal $signal): void
    {
        $item = $signal->item;
    
        if ($this->condition->isTrueFor($item->value, $item->key)) {
            $item->value = $this->mapper->map($item->value, $item->key);
        } elseif ($this->elseMapper !== null) {
            $item->value = $this->elseMapper->map($item->value, $item->key);
        }
    
        $this->next->handle($signal);
    }
    
    public function isBarren(): bool
    {
        return $this->elseMapper === null && $this->mapper instanceof Value;
    }
}