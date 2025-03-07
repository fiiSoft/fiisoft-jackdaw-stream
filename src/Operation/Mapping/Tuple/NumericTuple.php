<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Mapping\Tuple;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Mapping\Tuple;

final class NumericTuple extends Tuple
{
    private int $index = -1;
    
    public function handle(Signal $signal): void
    {
        $signal->item->value = [$signal->item->key, $signal->item->value];
        $signal->item->key = ++$this->index;
        
        $this->next->handle($signal);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            yield ++$this->index => [$key, $value];
        }
    }
}