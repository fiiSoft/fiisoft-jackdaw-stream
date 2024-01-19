<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Mapping\UnpackTuple;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Mapping\UnpackTuple;

final class UnpackNumericTuple extends UnpackTuple
{
    public function handle(Signal $signal): void
    {
        $item = $signal->item;
        
        $item->key = $item->value[0];
        $item->value = $item->value[1];
        
        $this->next->handle($signal);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $value) {
            yield $value[0] => $value[1];
        }
    }
}