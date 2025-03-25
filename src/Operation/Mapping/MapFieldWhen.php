<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Mapping;

use FiiSoft\Jackdaw\Filter\FilterReady;
use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Mapper\Mapper;
use FiiSoft\Jackdaw\Mapper\MapperReady;
use FiiSoft\Jackdaw\Mapper\Mappers;

final class MapFieldWhen extends ConditionalMap
{
    /** @var string|int */
    private $field;
    
    /**
     * @param string|int $field
     * @param FilterReady|callable|array<string|int, mixed>|scalar $condition
     * @param MapperReady|callable|iterable|mixed $mapper
     * @param MapperReady|callable|iterable|mixed|null $elseMapper
     */
    public function __construct($field, $condition, $mapper, $elseMapper = null)
    {
        parent::__construct($condition, $mapper, $elseMapper);
        
        $this->field = Helper::validField($field, 'field');
    }
    
    public function handle(Signal $signal): void
    {
        $item = $signal->item;
        
        if ($this->condition->isAllowed($item->value[$this->field], $item->key)) {
            $item->value[$this->field] = $this->mapper->map($item->value[$this->field], $item->key);
        } elseif ($this->elseMapper !== null) {
            $item->value[$this->field] = $this->elseMapper->map($item->value[$this->field], $item->key);
        }
    
        $this->next->handle($signal);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            
            if ($this->condition->isAllowed($value[$this->field], $key)) {
                $value[$this->field] = $this->mapper->map($value[$this->field], $key);
            } elseif ($this->elseMapper !== null) {
                $value[$this->field] = $this->elseMapper->map($value[$this->field], $key);
            }
            
            yield $key => $value;
        }
    }
    
    public function getMaper(): Mapper
    {
        return Mappers::mapField($this->field, $this->mapper);
    }
}