<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Condition\Condition;
use FiiSoft\Jackdaw\Condition\Conditions;
use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Mapper\Mapper;
use FiiSoft\Jackdaw\Mapper\Mappers;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Predicate\Predicate;

final class MapWhen extends BaseOperation
{
    /** @var Condition */
    private $condition;
    
    /** @var Mapper */
    private $mapper;
    
    /** @var Mapper|null */
    private $elseMapper;
    
    /**
     * @param Condition|Predicate|Filter|callable $condition
     * @param Mapper|callable $mapper
     * @param Mapper|callable|null $elseMapper
     */
    public function __construct($condition, $mapper, $elseMapper = null)
    {
        $this->condition = Conditions::getAdapter($condition);
        $this->mapper = Mappers::getAdapter($mapper);
        
        if ($elseMapper !== null) {
            $this->elseMapper = Mappers::getAdapter($elseMapper);
        }
    }
    
    public function handle(Signal $signal)
    {
        $item = $signal->item;
    
        if ($this->condition->isTrueFor($item->value, $item->key)) {
            $item->value = $this->mapper->map($item->value, $item->key);
        } elseif ($this->elseMapper !== null) {
            $item->value = $this->elseMapper->map($item->value, $item->key);
        }
    
        $this->next->handle($signal);
    }
}