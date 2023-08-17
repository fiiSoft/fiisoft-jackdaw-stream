<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Mapper\Mapper;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class MapKeyValue extends BaseOperation
{
    /** @var callable */
    private $keyValueMapper;
    
    private int $numOfArgs;
    
    public function __construct(callable $keyValueMapper)
    {
        $this->keyValueMapper = $keyValueMapper;
        $this->numOfArgs = Helper::getNumOfArgs($keyValueMapper);
    
        if ($this->numOfArgs > 2) {
            throw Helper::wrongNumOfArgsException('KeyValue mapper', $this->numOfArgs, 0, 1, 2);
        }
    
        if (!Helper::isDeclaredReturnTypeArray($this->keyValueMapper)) {
            throw new \LogicException('KeyValue mapper must have declared array as its return type');
        }
    }
    
    public function handle(Signal $signal): void
    {
        $item = $signal->item;
    
        if ($this->numOfArgs === 1) {
            $keyValuePair = ($this->keyValueMapper)($item->value);
        } elseif ($this->numOfArgs === 2) {
            $keyValuePair = ($this->keyValueMapper)($item->value, $item->key);
        } else {
            $keyValuePair = ($this->keyValueMapper)();
        }
        
        if (\count($keyValuePair) === 1) {
            $value = \reset($keyValuePair);
            
            $item->key = \key($keyValuePair);
            $item->value = $value instanceof Mapper ? $value->map($item->value, $item->key) : $value;
            
            $this->next->handle($signal);
        } else {
            throw new \LogicException('Result returned from KeyValue mapper is invalid');
        }
    }
}