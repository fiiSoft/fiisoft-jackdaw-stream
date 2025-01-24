<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Mapping\MapKeyValue;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Mapping\MapKeyValue;

final class ZeroArgKVMap extends MapKeyValue
{
    public function handle(Signal $signal): void
    {
        $keyValuePair = ($this->callable)();
        
        $signal->item->value = \current($keyValuePair);
        $signal->item->key = \key($keyValuePair);
        
        $this->next->handle($signal);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $_) {
            $keyValuePair = ($this->callable)();
            
            yield \key($keyValuePair) => \current($keyValuePair);
        }
    }
}