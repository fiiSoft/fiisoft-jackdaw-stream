<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Mapping\UnpackTuple;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Mapping\UnpackTuple;

final class UnpackAssocTuple extends UnpackTuple
{
    public function handle(Signal $signal): void
    {
        $item = $signal->item;
        
        $item->key = $item->value['key'];
        $item->value = $item->value['value'];
        
        $this->next->handle($signal);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $value) {
            yield $value['key'] => $value['value'];
        }
    }
}