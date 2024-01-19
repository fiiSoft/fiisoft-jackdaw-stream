<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Mapping\Tuple;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Mapping\Tuple;

final class AssocTuple extends Tuple
{
    private int $index = 0;
    
    public function handle(Signal $signal): void
    {
        $signal->item->value = ['key' => $signal->item->key, 'value' => $signal->item->value];
        $signal->item->key = $this->index++;
        
        $this->next->handle($signal);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            yield $this->index++ => ['key' => $key, 'value' => $value];
        }
    }
}