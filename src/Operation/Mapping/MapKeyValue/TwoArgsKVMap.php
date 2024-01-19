<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Mapping\MapKeyValue;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Mapper\Mapper;
use FiiSoft\Jackdaw\Operation\Mapping\MapKeyValue;

final class TwoArgsKVMap extends MapKeyValue
{
    public function handle(Signal $signal): void
    {
        $item = $signal->item;
        
        $keyValuePair = ($this->callable)($item->value, $item->key);
        $value = \reset($keyValuePair);
        
        $item->key = \key($keyValuePair);
        $item->value = $value instanceof Mapper ? $value->map($item->value, $item->key) : $value;
        
        $this->next->handle($signal);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            $keyValuePair = ($this->callable)($value, $key);
            
            $newValue = \reset($keyValuePair);
            $key = \key($keyValuePair);
            
            yield $key => $newValue instanceof Mapper ? $newValue->map($value, $key) : $newValue;
        }
    }
}