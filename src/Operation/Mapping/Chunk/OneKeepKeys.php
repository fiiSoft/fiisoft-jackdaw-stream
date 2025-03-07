<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Mapping\Chunk;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Mapping\Chunk;

final class OneKeepKeys extends Chunk
{
    public function handle(Signal $signal): void
    {
        $signal->item->value = [$signal->item->key => $signal->item->value];
        $signal->item->key = ++$this->index;
        
        $this->next->handle($signal);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            yield ++$this->index => [$key => $value];
        }
    }
}