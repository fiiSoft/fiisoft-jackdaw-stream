<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Mapping;

use FiiSoft\Jackdaw\Condition\Condition;
use FiiSoft\Jackdaw\Condition\ConditionReady;
use FiiSoft\Jackdaw\Condition\Conditions;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Mapper\Mapper;
use FiiSoft\Jackdaw\Mapper\MapperReady;
use FiiSoft\Jackdaw\Mapper\Mappers;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class MapWhile extends BaseOperation
{
    private Condition $condition;
    private Mapper $mapper;
    
    private bool $until, $isActive = true;
    
    /**
     * @param ConditionReady|callable $condition
     * @param MapperReady|callable|iterable|mixed $mapper
     */
    public function __construct($condition, $mapper, bool $until = false)
    {
        $this->condition = Conditions::getAdapter($condition);
        $this->mapper = Mappers::getAdapter($mapper);
        $this->until = $until;
    }
    
    public function handle(Signal $signal): void
    {
        if ($this->until XOR $this->condition->isTrueFor($signal->item->value, $signal->item->key)) {
            $signal->item->value = $this->mapper->map($signal->item->value, $signal->item->key);
        } else {
            $signal->forget($this);
        }
        
        $this->next->handle($signal);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($this->isActive) {
                if ($this->until XOR $this->condition->isTrueFor($value, $key)) {
                    yield $key => $this->mapper->map($value, $key);
                } else {
                    $this->isActive = false;
                    yield $key => $value;
                }
            } else {
                yield $key => $value;
            }
        }
    }
}