<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Mapping\Chunk;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Mapping\Chunk;

final class OneReindexKeys extends Chunk
{
    public function handle(Signal $signal): void
    {
        $signal->item->value = [$signal->item->value];
        $signal->item->key = $this->index++;
        
        $this->next->handle($signal);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $value) {
            yield $this->index++ => [$value];
        }
    }
}