<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Condition\ConditionReady;
use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Mapper\Mapper;
use FiiSoft\Jackdaw\Mapper\MapperReady;
use FiiSoft\Jackdaw\Mapper\Mappers;
use FiiSoft\Jackdaw\Operation\Internal\ConditionalMapOperation;

final class MapFieldWhen extends ConditionalMapOperation
{
    /** @var string|int */
    private $field;
    
    /**
     * @param string|int $field
     * @param ConditionReady|callable $condition
     * @param MapperReady|callable|iterable|mixed $mapper
     * @param MapperReady|callable|iterable|mixed|null $elseMapper
     */
    public function __construct($field, $condition, $mapper, $elseMapper = null)
    {
        if (Helper::isFieldValid($field)) {
            $this->field = $field;
        } else {
            throw new \InvalidArgumentException('Invalid param field');
        }
        
        parent::__construct($condition, $mapper, $elseMapper);
    }
    
    
    public function handle(Signal $signal): void
    {
        $item = $signal->item;
        
        if (\is_array($item->value)) {
            if (!\array_key_exists($this->field, $item->value)) {
                throw new \RuntimeException('Field '.$this->field.' does not exist in value');
            }
        } elseif ($item->value instanceof \ArrayAccess) {
            if (!isset($item->value[$this->field])) {
                throw new \RuntimeException('Field '.$this->field.' does not exist in value');
            }
        } else {
            throw new \LogicException(
                'Unable to map field '.$this->field.' because value is '.Helper::typeOfParam($item->value)
            );
        }
        
        if ($this->condition->isTrueFor($item->value[$this->field], $item->key)) {
            $item->value[$this->field] = $this->mapper->map($item->value[$this->field], $item->key);
        } elseif ($this->elseMapper !== null) {
            $item->value[$this->field] = $this->elseMapper->map($item->value[$this->field], $item->key);
        }
    
        $this->next->handle($signal);
    }
    
    public function getMaper(): Mapper
    {
        return Mappers::mapField($this->field, $this->mapper);
    }
}