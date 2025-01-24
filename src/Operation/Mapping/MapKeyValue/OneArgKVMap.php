<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Mapping\MapKeyValue;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Mapping\MapKeyValue;

final class OneArgKVMap extends MapKeyValue
{
    public function handle(Signal $signal): void
    {
        $keyValuePair = ($this->callable)($signal->item->value);
        
        $signal->item->value = \current($keyValuePair);
        $signal->item->key = \key($keyValuePair);
        
        $this->next->handle($signal);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $value) {
            $keyValuePair = ($this->callable)($value);
            
            yield \key($keyValuePair) => \current($keyValuePair);
        }
    }
}