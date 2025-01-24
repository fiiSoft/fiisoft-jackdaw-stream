<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Mapping\MapKeyValue;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Mapping\MapKeyValue;

final class TwoArgsKVMap extends MapKeyValue
{
    public function handle(Signal $signal): void
    {
        $keyValuePair = ($this->callable)($signal->item->value, $signal->item->key);
        
        $signal->item->key = \key($keyValuePair);
        $signal->item->value = \current($keyValuePair);
        
        $this->next->handle($signal);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            $keyValuePair = ($this->callable)($value, $key);
            
            yield \key($keyValuePair) => \current($keyValuePair);
        }
    }
}