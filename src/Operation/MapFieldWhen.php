<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Condition\Condition;
use FiiSoft\Jackdaw\Condition\Conditions;
use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Mapper\Mapper;
use FiiSoft\Jackdaw\Mapper\Mappers;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Predicate\Predicate;
use FiiSoft\Jackdaw\Reducer\Reducer;

final class MapFieldWhen extends BaseOperation
{
    private Condition $condition;
    
    private Mapper $mapper;
    private ?Mapper $elseMapper = null;
    
    /** @var string|int */
    private $field;
    
    /**
     * @param string|int $field
     * @param Condition|Predicate|Filter|callable $condition
     * @param Mapper|Reducer|callable|mixed $mapper
     * @param Mapper|Reducer|callable|mixed|null $elseMapper
     */
    public function __construct($field, $condition, $mapper, $elseMapper = null)
    {
        if (Helper::isFieldValid($field)) {
            $this->field = $field;
        } else {
            throw new \InvalidArgumentException('Invalid param field');
        }
        
        $this->condition = Conditions::getAdapter($condition);
        $this->mapper = Mappers::getAdapter($mapper);
    
        if ($elseMapper !== null) {
            $this->elseMapper = Mappers::getAdapter($elseMapper);
        }
    }
    
    
    public function handle(Signal $signal): void
    {
        $item = $signal->item;
        
        if (\is_array($item->value) || $item->value instanceof \ArrayAccess) {
            if (\array_key_exists($this->field, $item->value)) {
                if ($this->condition->isTrueFor($item->value[$this->field], $item->key)) {
                    $item->value[$this->field] = $this->mapper->map($item->value[$this->field], $item->key);
                } elseif ($this->elseMapper !== null) {
                    $item->value[$this->field] = $this->elseMapper->map($item->value[$this->field], $item->key);
                }
            } else {
                throw new \RuntimeException('Field '.$this->field.' does not exist in value');
            }
        } else {
            throw new \LogicException(
                'Unable to map field '.$this->field.' because value is '.Helper::typeOfParam($item->value)
            );
        }
    
        $this->next->handle($signal);
    }
}